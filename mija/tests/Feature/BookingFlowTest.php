<?php

namespace Tests\Feature;

use App\Models\Amenity;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Property;
use App\Models\User;
use App\Support\BookingService;
use App\Support\Jalali;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingFlowTest extends TestCase
{
    use RefreshDatabase;

    private function property(bool $instant = false, string $policy = 'moderate'): Property
    {
        $host = User::factory()->create(['role' => 'host', 'phone' => '09120001111']);

        return Property::create([
            'host_id' => $host->id, 'title' => 'ویلای تست', 'type' => 'villa', 'province' => 'مازندران', 'city' => 'نوشهر',
            'description' => str_repeat('توضیحات ', 10), 'capacity_base' => 4, 'capacity_max' => 6,
            'price_weekday' => 1000000, 'price_weekend' => 1500000, 'extra_guest_fee' => 100000, 'cleaning_fee' => 200000,
            'instant_booking' => $instant, 'cancellation_policy' => $policy, 'status' => 'approved',
        ]);
    }

    private function guest(): User
    {
        return User::factory()->create(['role' => 'guest', 'phone' => '09120002222']);
    }

    public function test_jalali_round_trip(): void
    {
        $this->assertSame([1405, 8, 6], Jalali::toJalali(2026, 10, 28));
        $this->assertSame([2026, 10, 28], Jalali::toGregorian(1405, 8, 6));
        $this->assertSame('۱۴۰۵/۰۸/۰۶', Jalali::format('2026-10-28'));
    }

    public function test_quote_weekend_and_extras(): void
    {
        $p = $this->property();
        // پنجشنبه تا شنبه: دو شب آخر هفته (پنجشنبه/جمعه)
        $in = today()->next('Thursday');
        $q = $p->quote($in, $in->copy()->addDays(2), 5);
        $this->assertTrue($q['ok']);
        $this->assertSame(3000000, $q['base']);
        $this->assertSame(2 * 100000, $q['extra']);
        $this->assertSame(3000000 + 200000 + 200000, $q['subtotal']);
        $this->assertSame(intdiv($q['subtotal'] * 10, 100), $q['commission']);
    }

    public function test_instant_booking_pay_and_settle(): void
    {
        $p = $this->property(true);
        $g = $this->guest();
        $b = BookingService::create($g, $p, today()->addDays(10), today()->addDays(12), 2, 0, null, null);
        $this->assertSame('awaiting_payment', $b->status);

        $pay = Payment::create(['user_id' => $g->id, 'booking_id' => $b->id, 'amount' => $b->total, 'authority' => 'abc']);
        BookingService::markPaid($b->fresh(), $pay, 'X1');
        $this->assertSame('confirmed', $b->fresh()->status);

        // همزمانی: بازه‌ی هم‌پوشان قابل رزرو نیست
        $this->assertFalse($p->isAvailable(today()->addDays(11), today()->addDays(13)));
        $this->assertTrue($p->isAvailable(today()->addDays(12), today()->addDays(14)));

        $b->fresh()->update(['check_in' => today(), 'check_out' => today()->addDay()]);
        BookingService::tick();
        $this->assertNotNull($b->fresh()->settled_at);
        $this->assertSame($b->host_payout, $p->host->fresh()->wallet);
    }

    public function test_request_booking_accept_reject_and_expire(): void
    {
        $p = $this->property(false);
        $g = $this->guest();
        $b = BookingService::create($g, $p, today()->addDays(5), today()->addDays(7), 2, 0, null, null);
        $this->assertSame('pending_host', $b->status);
        BookingService::accept($b);
        $this->assertSame('awaiting_payment', $b->fresh()->status);

        $b2 = BookingService::create($g, $p, today()->addDays(20), today()->addDays(22), 2, 0, null, null);
        $b2->update(['respond_by' => now()->subMinute()]);
        BookingService::tick();
        $this->assertSame('expired', $b2->fresh()->status);
    }

    public function test_guest_cancellation_refunds_by_policy(): void
    {
        $p = $this->property(true, 'moderate');
        $g = $this->guest();
        $b = BookingService::create($g, $p, today()->addDays(30), today()->addDays(32), 2, 0, null, null);
        $pay = Payment::create(['user_id' => $g->id, 'booking_id' => $b->id, 'amount' => $b->total, 'authority' => 'z']);
        BookingService::markPaid($b->fresh(), $pay, 'X2');

        $refund = BookingService::cancelByGuest($b->fresh());
        $this->assertSame($b->total, $refund);          // بیش از ۷ روز → ۱۰۰٪
        $this->assertSame($b->total, $g->fresh()->wallet);
    }


    public function test_referral_bonus_on_first_payment(): void
    {
        $p = $this->property(true);
        $ref = User::factory()->create(['role' => 'guest', 'phone' => '09120003333']);
        $g = User::factory()->create(['role' => 'guest', 'phone' => '09120004444', 'referred_by' => $ref->id]);
        $b = BookingService::create($g, $p, today()->addDays(10), today()->addDays(11), 2, 0, null, null);
        $pay = Payment::create(['user_id' => $g->id, 'booking_id' => $b->id, 'amount' => $b->total, 'authority' => 'r1']);
        BookingService::markPaid($b->fresh(), $pay, 'R1');

        $this->assertSame(config('mija.referral_bonus'), $g->fresh()->wallet);
        $this->assertSame(config('mija.referral_bonus'), $ref->fresh()->wallet);
    }

    public function test_loyalty_level_and_cashback(): void
    {
        $p = $this->property(true);
        $g = $this->guest();
        $this->assertSame('bronze', $g->level()['level']['key']);
        $old = BookingService::create($g, $p, today()->addDays(40), today()->addDays(46), 2, 0, null, null);
        $old->update(['status' => 'completed']);
        $this->assertSame('silver', $g->fresh()->level()['level']['key']);

        $b = BookingService::create($g, $p, today()->addDays(10), today()->addDays(11), 2, 0, null, null);
        $b->update(['status' => 'confirmed', 'check_in' => today()->subDays(3), 'check_out' => today()->subDay(), 'settled_at' => now()]);
        BookingService::tick();
        $this->assertSame(intdiv($b->total * 1, 100), $g->fresh()->wallet);
    }

    public function test_role_profiles_ics_invoice_and_public_host(): void
    {
        $p = $this->property(true);
        $g = $this->guest();
        $b = BookingService::create($g, $p, today()->addDays(10), today()->addDays(12), 2, 0, null, null);

        $this->actingAs($g)->get('/profile')->assertOk()->assertSee('سطح وفاداری');
        $this->actingAs($g)->get(route('booking.ics', $b))->assertOk()->assertHeader('Content-Type', 'text/calendar; charset=utf-8');
        $this->actingAs($g)->get(route('booking.invoice', $b))->assertOk()->assertSee($b->code);
        $this->actingAs($p->host)->get('/profile')->assertOk()->assertSee('نرخ پاسخ');
        $this->actingAs($p->host)->get('/host')->assertOk();
        $admin = User::factory()->create(['role' => 'admin', 'phone' => '09120005555']);
        $this->actingAs($admin)->get('/profile')->assertOk()->assertSee('امنیت حساب');
        $this->actingAs($admin)->get('/admin')->assertOk();
        $this->get(route('host.public', $p->host))->assertOk()->assertSee($p->host->name);
        // دیگران به فاکتور دسترسی ندارند
        $other = User::factory()->create(['role' => 'guest', 'phone' => '09120006666']);
        $this->actingAs($other)->get(route('booking.invoice', $b))->assertNotFound();
    }

    public function test_panels_are_role_protected(): void
    {
        $g = $this->guest();
        $this->actingAs($g)->get('/host')->assertForbidden();
        $this->actingAs($g)->get('/admin')->assertForbidden();
        $this->actingAs($g)->get('/panel')->assertOk();
    }

    public function test_guests_redirected_to_login(): void
    {
        $this->get('/panel')->assertRedirect('/login');
    }

    public function test_public_pages_render(): void
    {
        $this->seed();
        $this->get('/')->assertOk()->assertSee('می‌جا');
        $this->get('/search')->assertOk();
        $this->assertFileExists(public_path('manifest.webmanifest'));
        $p = Property::first();
        if ($p) {
            $this->get(route('property', $p))->assertOk();
        }
    }
}
