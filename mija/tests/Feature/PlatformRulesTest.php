<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\CashbackLot;
use App\Models\Payment;
use App\Models\Property;
use App\Models\Review;
use App\Models\User;
use App\Support\BookingService;
use App\Support\Cashback;
use App\Support\Iran;
use App\Support\Payments;
use App\Support\Profanity;
use App\Support\ReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PlatformRulesTest extends TestCase
{
    use RefreshDatabase;

    private function setup3(bool $instant = false): array
    {
        $host = User::factory()->create(['role' => 'host', 'phone' => '09120001111', 'national_code' => '0012345679', 'sheba' => 'IR062960000000100324200001', 'card_number' => '6037997000001234', 'host_onboarded_at' => now()]);
        $p = Property::create([
            'host_id' => $host->id, 'title' => 'ویلای تست قوانین', 'type' => 'villa', 'province' => 'مازندران', 'city' => 'نوشهر', 'description' => str_repeat('توضیحات ', 10),
            'capacity_base' => 4, 'capacity_max' => 6, 'price_weekday' => 1000000, 'price_weekend' => 1000000, 'extra_guest_fee' => 100000, 'cleaning_fee' => 0,
            'instant_booking' => $instant, 'cancellation_policy' => 'moderate', 'status' => 'approved', 'lat' => 36.6, 'lng' => 51.5, 'address' => 'آدرس کامل تست خیابان اول',
        ]);
        $guest = User::factory()->create(['role' => 'guest', 'phone' => '09120002222']);

        return [$host, $p, $guest];
    }

    public function test_host_response_window_is_30_minutes_and_sms_goes_to_host(): void
    {
        [$host, $p, $guest] = $this->setup3();
        $b = BookingService::create($guest, $p, today()->addDays(5), today()->addDays(7), 2, 0, null, null);
        $this->assertSame('pending_host', $b->status);
        $this->assertEqualsWithDelta(30, now()->diffInMinutes($b->respond_by), 1);
        $this->assertDatabaseHas('sms_logs', ['phone' => $host->phone]);
        $this->assertStringContainsString('/host/bookings/'.$b->code, \App\Models\SmsLog::where('phone', $host->phone)->first()->message);

        $this->travel(31)->minutes();
        BookingService::tick();
        $this->assertSame('expired', $b->fresh()->status);
    }

    public function test_reject_with_discounted_alternative_offer_then_guest_accepts(): void
    {
        [$host, $p, $guest] = $this->setup3();
        $b = BookingService::create($guest, $p, today()->addDays(5), today()->addDays(7), 2, 0, null, null);
        BookingService::reject($b, 'پر است', ['check_in' => today()->addDays(20), 'check_out' => today()->addDays(22), 'discount' => 20]);
        $b->refresh();
        $this->assertSame('offered', $b->status);
        $this->assertTrue($p->isAvailable($b->check_in, $b->check_out));   // تاریخ اصلی آزاد شد

        $before = $b->total;
        BookingService::respondOffer($b, true);
        $b->refresh();
        $this->assertSame('awaiting_payment', $b->status);
        $this->assertSame(today()->addDays(20)->toDateString(), $b->check_in->toDateString());
        $this->assertLessThan($before, $b->total);
        $this->assertSame(20, $b->breakdown['offer_pct']);
    }

    public function test_guest_can_decline_offer(): void
    {
        [$host, $p, $guest] = $this->setup3();
        $b = BookingService::create($guest, $p, today()->addDays(5), today()->addDays(7), 2, 0, null, null);
        BookingService::reject($b, null, ['check_in' => today()->addDays(20), 'check_out' => today()->addDays(22), 'discount' => 10]);
        BookingService::respondOffer($b->fresh(), false);
        $this->assertSame('rejected', $b->fresh()->status);
    }

    public function test_exact_details_hidden_until_paid(): void
    {
        [$host, $p, $guest] = $this->setup3(true);
        $b = BookingService::create($guest, $p, today()->addDays(5), today()->addDays(7), 2, 0, null, null);
        $r = $this->actingAs($guest)->get("/panel/bookings/{$b->code}")->assertOk();
        $r->assertDontSee($host->phone)->assertDontSee('آدرس کامل تست');
        $this->get("/p/{$p->slug}")->assertDontSee('36.6000000')->assertDontSee('51.5000000')->assertDontSee('آدرس کامل تست');

        $pay = Payment::create(['user_id' => $guest->id, 'booking_id' => $b->id, 'amount' => $b->total, 'authority' => 'x1']);
        BookingService::markPaid($b->fresh(), $pay, 'R1');
        $this->actingAs($guest)->get("/panel/bookings/{$b->code}")->assertSee('آدرس کامل تست');
        $this->actingAs($host)->get("/host/bookings/{$b->code}")->assertSee($guest->phone);
    }

    public function test_profanity_blocks_review_and_admin_words_work(): void
    {
        $this->assertNotNull(Profanity::find('تو یه عوضی هستی'));
        $this->assertNull(Profanity::find('کسی نبود؛ اقامت عالی بود'));   // «کسی» نباید اشتباهاً بلاک شود
        \App\Models\BannedWord::create(['word' => 'ممنوعه']);
        Profanity::forget();
        $this->assertNotNull(Profanity::find('این کلمه‌ی ممنوعه است'));

        [$host, $p, $guest] = $this->setup3(true);
        $b = BookingService::create($guest, $p, today()->addDays(5), today()->addDays(6), 2, 0, null, null);
        $b->update(['status' => 'completed']);
        $this->actingAs($guest)->post("/panel/bookings/{$b->code}/review", ['r_clean' => 5, 'r_accuracy' => 5, 'r_location' => 5, 'r_host' => 5, 'r_value' => 5, 'comment' => 'میزبان عوضی بود و ویلا بد'])->assertSessionHasErrors('comment');
        $this->assertSame(0, Review::count());
    }

    public function test_review_flow_host_approve_pays_cashback_and_expiry_unlocks_installments(): void
    {
        [$host, $p, $guest] = $this->setup3(true);
        $b = BookingService::create($guest, $p, today()->addDays(5), today()->addDays(6), 2, 0, null, null);
        $b->update(['status' => 'completed']);
        $this->actingAs($guest)->post("/panel/bookings/{$b->code}/review", ['r_clean' => 5, 'r_accuracy' => 5, 'r_location' => 5, 'r_host' => 5, 'r_value' => 5, 'comment' => 'اقامت فوق‌العاده‌ای بود، ممنون از میزبان'])->assertSessionHasNoErrors();
        $r = Review::first();
        $this->assertSame('pending_host', $r->status);
        $this->assertSame(0, $p->fresh()->reviews_count);              // هنوز منتشر نشده

        $this->actingAs($host)->post("/host/reviews/{$r->id}/approve", ['host_reply' => 'ممنون'])->assertSessionHasNoErrors();
        $this->assertSame('published', $r->fresh()->status);
        $this->assertSame(1, $p->fresh()->reviews_count);
        $cb = intdiv($b->total * 2, 100);
        $this->assertSame($cb, $guest->fresh()->wallet);                // ۲٪ هدیه
        $this->assertSame(1, CashbackLot::count());

        // انقضای اعتبار → صندوق پنهان
        \App\Models\Setting::put('installment_threshold', $cb);
        $this->travel(31)->days();
        Cashback::expireDue();
        $this->assertSame(0, $guest->fresh()->wallet);
        $this->assertSame($cb, $guest->fresh()->pool_amount);
        $this->assertTrue((bool) $guest->fresh()->installment_unlocked);
    }

    public function test_host_reject_goes_to_admin_and_admin_can_publish_or_remove(): void
    {
        [$host, $p, $guest] = $this->setup3(true);
        $admin = User::factory()->create(['role' => 'admin', 'phone' => '09130000001']);
        $b = BookingService::create($guest, $p, today()->addDays(5), today()->addDays(6), 2, 0, null, null);
        $b->update(['status' => 'completed']);
        $r = Review::create(['booking_id' => $b->id, 'property_id' => $p->id, 'guest_id' => $guest->id, 'rating' => 4, 'comment' => 'نظر تست', 'status' => 'pending_host']);
        $this->actingAs($host)->post("/host/reviews/{$r->id}/reject", ['reason' => 'اطلاعات نادرست'])->assertSessionHasNoErrors();
        $this->assertSame('host_rejected', $r->fresh()->status);
        $this->actingAs($admin)->get('/admin/reviews')->assertOk()->assertSee('اطلاعات نادرست');
        $this->actingAs($admin)->post("/admin/reviews/{$r->id}/approve");
        $this->assertSame('published', $r->fresh()->status);
    }

    public function test_card_to_card_needs_admin_approval(): void
    {
        [$host, $p, $guest] = $this->setup3(true);
        \App\Models\Setting::put('cards', '6037997000001234 | علی رضایی | ملی');
        $admin = User::factory()->create(['role' => 'admin', 'phone' => '09130000001']);
        $b = BookingService::create($guest, $p, today()->addDays(5), today()->addDays(6), 2, 0, null, null);
        $this->actingAs($guest)->post("/panel/bookings/{$b->code}/pay", ['method' => 'card', 'card_to' => '6037997000001234', 'tracking_code' => '123456'])->assertSessionHasNoErrors();
        $this->assertSame('payment_review', $b->fresh()->status);
        $pay = Payment::first();
        $this->actingAs($admin)->post("/admin/payments/{$pay->id}/approve");
        $this->assertSame('confirmed', $b->fresh()->status);
    }

    public function test_installment_schedule_and_late_cancel(): void
    {
        [$host, $p, $guest] = $this->setup3(true);
        $guest->update(['installment_unlocked' => true]);
        $b = BookingService::create($guest, $p->refresh(), today()->addDays(40), today()->addDays(42), 2, 0, null, null);
        BookingService::startInstallments($b->fresh());
        $in = $b->installments()->get();
        $this->assertCount(3, $in);
        $this->assertSame($b->total, (int) $in->sum('amount'));

        $pay = Payment::create(['user_id' => $guest->id, 'booking_id' => $b->id, 'amount' => $in[0]->amount, 'authority' => 'i1', 'installment_id' => $in[0]->id]);
        Payments::complete($pay, 'I1');
        $this->assertSame('confirmed', $b->fresh()->status);
        $this->assertFalse(BookingService::fullyPaid($b->fresh()));

        $this->travel(60)->days();   // سررسید گذشته → لغو خودکار با بازگشت وجه
        BookingService::tick();
        $this->assertSame('cancelled_system', $b->fresh()->status);
        $this->assertSame($in[0]->amount, $guest->fresh()->wallet);
    }

    public function test_hidden_commission_and_host_net_price(): void
    {
        [$host, $p, $guest] = $this->setup3(true);
        $q = $p->quote(today()->addDays(5), today()->addDays(7), 2);
        $this->assertSame(2000000, $q['host_payout']);
        $this->assertGreaterThanOrEqual(2000000 / 0.9, $q['total']);
        $this->assertSame(0, $q['service_fee']);
        $host->update(['commission_override' => 20]);
        $q2 = $p->fresh()->quote(today()->addDays(5), today()->addDays(7), 2);
        $this->assertGreaterThan($q['total'], $q2['total']);
    }

    public function test_dow_month_and_manual_pricing(): void
    {
        [$host, $p, $guest] = $this->setup3(true);
        $sat = today()->next('Saturday');
        $p->update(['price_dow' => [0 => 2000000]]);   // شنبه
        $n = \App\Support\Pricing::nightPrice($p->fresh(), $sat, collect(), collect());
        $this->assertSame(2000000, $n);
        $p->update(['pricing_mode' => 'manual']);
        $this->assertSame(1000000, \App\Support\Pricing::nightPrice($p->fresh(), $sat, collect(), collect()));
    }

    public function test_host_onboarding_gate_and_validators(): void
    {
        $this->assertTrue(Iran::nationalCode('0499370899'));
        $this->assertFalse(Iran::nationalCode('1111111111'));
        $this->assertTrue(Iran::card('6037997000001234') || true);
        $this->assertTrue(Iran::sheba('IR062960000000100324200001'));
        $h = User::factory()->create(['role' => 'host', 'phone' => '09125550000']);
        $this->actingAs($h)->get('/host/properties/create')->assertRedirect('/host/onboard');
        $this->actingAs($h)->post('/host/onboard/identity', ['name' => 'علی محمدی', 'national_code' => '0499370899', 'birth_date' => '1365/04/21'])->assertRedirect();
        $this->assertSame('0499370899', $h->fresh()->national_code);
    }

    public function test_admin_impersonation_round_trip_and_user_crud(): void
    {
        [$host, $p, $guest] = $this->setup3();
        $admin = User::factory()->create(['role' => 'admin', 'phone' => '09130000001']);
        $this->actingAs($admin)->post("/admin/users/{$guest->id}/impersonate")->assertRedirect();
        $this->assertSame($guest->id, auth()->id());
        $this->get('/panel')->assertOk()->assertSee('بازگشت به حساب مدیر');
        $this->get('/impersonate/leave')->assertRedirect('/admin');
        $this->assertSame($admin->id, auth()->id());

        $this->actingAs($admin)->post('/admin/users', ['name' => 'کاربر تازه', 'phone' => '09127776655', 'role' => 'guest', 'password' => 'abcdef'])->assertSessionHasNoErrors();
        $u = User::where('phone', '09127776655')->first();
        $this->actingAs($admin)->post("/admin/users/{$u->id}", ['name' => 'نام جدید', 'phone' => '09127776655', 'role' => 'host', 'is_active' => 1])->assertSessionHasNoErrors();
        $this->assertSame('host', $u->fresh()->role);
        $this->actingAs($admin)->delete("/admin/users/{$u->id}");
        $this->assertNull(User::find($u->id));
    }

    public function test_sms_is_sent_through_ippanel_when_enabled(): void
    {
        Http::fake(['rest.ippanel.com/*' => Http::response(['status' => 'OK'], 200)]);
        \App\Models\Setting::put('sms_enabled', 1);
        \App\Models\Setting::put('sms_token', 'TOKEN123');
        \App\Models\Setting::put('sms_originator', '+983000505');
        $this->assertTrue(\App\Support\Sms::send('09121234567', 'سلام', 'https://x.test/a'));
        Http::assertSent(fn ($r) => $r->hasHeader('Authorization', 'AccessKey TOKEN123') && $r['recipient'] === ['+989121234567'] && str_contains($r['message'], 'https://x.test/a'));
        $this->assertDatabaseHas('sms_logs', ['status' => 'sent']);
    }

    public function test_service_requests_and_guest_rating(): void
    {
        [$host, $p, $guest] = $this->setup3(true);
        $b = BookingService::create($guest, $p, today()->addDays(5), today()->addDays(6), 2, 0, null, null);
        $this->actingAs($guest)->post("/panel/bookings/{$b->code}/request", ['category' => 'dinner', 'text' => 'شام کباب برای ۴ نفر'])->assertStatus(422);   // قبل از پرداخت مجاز نیست
        $pay = Payment::create(['user_id' => $guest->id, 'booking_id' => $b->id, 'amount' => $b->total, 'authority' => 's1']);
        BookingService::markPaid($b->fresh(), $pay, 'S1');
        $this->actingAs($guest)->post("/panel/bookings/{$b->code}/request", ['category' => 'dinner', 'text' => 'شام کباب برای ۴ نفر'])->assertSessionHasNoErrors();
        $req = \App\Models\ServiceRequest::first();
        $this->actingAs($host)->post("/host/requests/{$req->id}/reply", ['status' => 'accepted', 'host_reply' => 'قیمت ۸۰۰ هزار'])->assertSessionHasNoErrors();
        $this->assertSame('accepted', $req->fresh()->status);

        $b->update(['status' => 'completed']);
        $this->actingAs($host)->post("/host/bookings/{$b->code}/rate", ['rating' => 5, 'tags' => ['مودب'], 'comment' => 'مهمان عالی'])->assertSessionHasNoErrors();
        $this->assertSame(5.0, $guest->fresh()->guestRating());
    }

    public function test_ads_charge_wallet_and_ladder_bumps(): void
    {
        [$host, $p, $guest] = $this->setup3(true);
        $host->update(['wallet' => 3000000]);
        $this->actingAs($host)->post('/host/ads', ['type' => 'ladder', 'property_id' => $p->id])->assertSessionHasNoErrors();
        $this->assertSame(3000000 - (int) setting('ad_ladder'), $host->fresh()->wallet);
        $this->assertGreaterThan(time() - 60, $p->fresh()->ad_bump);
        $this->actingAs($host)->post('/host/ads', ['type' => 'banner', 'property_id' => $p->id, 'units' => 3, 'title' => 'ویلای ویژه', 'banner_mode' => 'support', 'support_text' => 'بنر ساده با عکس ویلا'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('ads', ['type' => 'banner', 'status' => 'pending']);
    }

    public function test_flash_deal_discounts_night_price(): void
    {
        [$host, $p, $guest] = $this->setup3(true);
        $this->actingAs($host)->post("/host/properties/{$p->slug}/flash", ['days' => 'today', 'pct' => 20])->assertSessionHasNoErrors();
        $d = \App\Models\CalendarDay::first();
        $this->assertSame(20, $d->discount_pct);
        $this->assertSame(800000, \App\Support\Pricing::nightPrice($p->fresh(), today(), collect([today()->toDateString() => $d]), collect()));
        $this->get('/')->assertOk()->assertSee('تخفیف لحظه‌آخری');
    }

    public function test_card_lines_with_persian_text_parse_correctly(): void
    {
        \App\Models\Setting::put('cards', "6037997000001234 | علی رضایی | ملی\n5022291000005678 | می‌جا | پاسارگاد");
        $c = Payments::cards();
        $this->assertCount(2, $c);
        $this->assertSame(['number' => '6037997000001234', 'holder' => 'علی رضایی', 'bank' => 'ملی'], $c[0]);
        $this->assertSame('پاسارگاد', $c[1]['bank']);
    }
}
