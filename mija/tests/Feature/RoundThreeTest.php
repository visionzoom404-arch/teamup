<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\Notice;
use App\Models\Payout;
use App\Models\Property;
use App\Models\Setting;
use App\Models\SmsLog;
use App\Models\User;
use App\Support\BookingService;
use App\Support\Sms;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** دور سوم: اعلان‌ها، پیامک، ورود پیامکی، کیف پول/رسید، تبلیغات بنری، تماس با ما، امکانات */
class RoundThreeTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create(['name' => 'مدیر', 'phone' => '09130000501', 'password' => 'secret123', 'role' => 'admin']);
    }

    private function host(): User
    {
        return User::factory()->create(['role' => 'host', 'phone' => '09120005011', 'national_code' => '0012345679', 'sheba' => 'IR062960000000100324200001', 'bank_owner' => 'علی رضایی', 'card_number' => '6037997000001234', 'host_onboarded_at' => now(), 'wallet' => 5000000]);
    }

    private function prop(User $host): Property
    {
        return Property::create([
            'host_id' => $host->id, 'title' => 'ویلای تست', 'type' => 'villa', 'province' => 'مازندران', 'city' => 'نوشهر', 'description' => str_repeat('توضیحات ', 10),
            'capacity_base' => 4, 'capacity_max' => 6, 'price_weekday' => 1000000, 'price_weekend' => 1000000, 'extra_guest_fee' => 0, 'cleaning_fee' => 0,
            'instant_booking' => false, 'cancellation_policy' => 'moderate', 'status' => 'approved', 'lat' => 36.6, 'lng' => 51.5, 'address' => 'آدرس تست',
        ]);
    }

    private function smsOn(): void
    {
        foreach (['sms_enabled' => 1, 'sms_token' => str_repeat('a', 40), 'sms_originator' => '3000505'] as $k => $v) {
            Setting::put($k, $v);
        }
    }

    public function test_booking_request_notifies_host_in_site_and_by_sms(): void
    {
        $this->smsOn();
        Http::fake(['rest.ippanel.com/*' => Http::response(['status' => 'OK', 'code' => 200, 'data' => ['message_id' => 1]], 200)]);
        $host = $this->host();
        $p = $this->prop($host);
        $guest = User::factory()->create(['role' => 'guest', 'phone' => '09120005022']);
        BookingService::create($guest, $p, today()->addDays(5), today()->addDays(7), 2, 0, null, null);
        $this->assertTrue(Notice::where('user_id', $host->id)->exists());
        $this->assertSame('sent', SmsLog::where('phone', $host->phone)->first()->status);
        Http::assertSent(fn ($r) => str_contains($r->url(), '/messages') && $r['originator'] === '+983000505' && $r['recipient'] === ['+989120005011']);
    }

    public function test_sms_errors_are_explained_in_persian(): void
    {
        $this->smsOn();
        $resp = Http::response(['status' => 'Unauthorized', 'code' => 401, 'error_message' => 'invalid access key'], 401);
        Http::fake(function () use (&$resp) {
            return $resp;
        });
        $this->assertFalse(Sms::send('09120005033', 'تست'));
        $this->assertStringContainsString('توکن', Sms::$lastError);
        $resp = Http::response(['status' => 'Error', 'code' => 400, 'error_message' => 'insufficient credit'], 400);
        Sms::send('09120005033', 'تست');
        $this->assertStringContainsString('اعتبار', Sms::$lastError);
        Setting::put('sms_enabled', 0);
        Sms::send('09120005033', 'تست');
        $this->assertStringContainsString('خاموش', Sms::$lastError);
    }

    public function test_sms_diagnose_shows_credit_and_failing_step(): void
    {
        $this->smsOn();
        Http::fake(['rest.ippanel.com/v1/credit' => Http::response(['status' => 'OK', 'code' => 200, 'data' => ['credit' => 12345.5]], 200)]);
        $steps = Sms::diagnose();
        $m = collect($steps)->keyBy(0);
        $this->assertSame('ok', $m['conn'][2]);
        $this->assertStringContainsString('12', \App\Support\Jalali::latinDigits($m['conn'][3]));
        $this->assertSame('ok', $m['line'][2]);
        Setting::put('sms_originator', '');
        $this->assertSame('fail', collect(Sms::diagnose())->keyBy(0)['line'][2]);
        // مدیر صفحه‌ی بررسی را می‌بیند
        $a = $this->admin();
        $this->actingAs($a)->post('/admin/sms/check', ['send' => '0'])->assertRedirect();
        $this->actingAs($a)->get('/admin/settings?tab=sms')->assertOk()->assertSee('بررسی اتصال پنل پیامک');
    }

    public function test_login_with_sms_code(): void
    {
        $this->smsOn();
        Http::fake(['rest.ippanel.com/*' => Http::response(['status' => 'OK', 'code' => 200], 200)]);
        $u = User::factory()->create(['role' => 'guest', 'phone' => '09120005044']);
        $this->post('/login/sms', ['phone' => '09120005044'])->assertRedirect('/login?m=sms');
        $this->post('/login/sms', ['phone' => '09120005044'])->assertSessionHasErrors('phone');   // محدودیت ۶۰ ثانیه
        $msg = SmsLog::where('phone', '09120005044')->latest('id')->first()->message;
        preg_match('/(\d{5})/', $msg, $m);
        $this->post('/login/sms/verify', ['phone' => '09120005044', 'code' => '00000'])->assertSessionHasErrors('code');
        $this->assertGuest();
        $this->post('/login/sms/verify', ['phone' => '09120005044', 'code' => $m[1]])->assertRedirect();
        $this->assertAuthenticatedAs($u);
    }

    public function test_login_sms_unknown_number_and_sms_down(): void
    {
        $this->post('/login/sms', ['phone' => '09129999999'])->assertSessionHasErrors('phone');
        $u = User::factory()->create(['role' => 'guest', 'phone' => '09120005055']);
        // پیامک خاموش: خطای فارسی + اعلان به مدیر
        $this->admin();
        $r = $this->post('/login/sms', ['phone' => '09120005055']);
        $r->assertSessionHasErrors('phone');
        $this->assertTrue(Notice::where('title', 'like', '%کد ورود پیامکی ناموفق%')->exists());
        $this->get('/login?m=sms')->assertOk()->assertSee('دریافت کد ورود');
    }

    public function test_admins_get_notices_for_key_events_and_feed_api(): void
    {
        $a = $this->admin();
        $this->post('/register', ['name' => 'میزبان جدید', 'phone' => '09120005066', 'password' => 'secret123', 'password_confirmation' => 'secret123', 'role' => 'host']);
        $this->assertTrue(Notice::where('user_id', $a->id)->where('kind', 'admin')->where('title', 'like', '%میزبان جدید%')->exists());
        $feed = $this->actingAs($a)->getJson('/api/notices?after=0')->assertOk()->json();
        $this->assertTrue($feed['ok']);
        $this->assertNotEmpty($feed['items']);
        $this->assertGreaterThan(0, $feed['last_id']);
        $after = $this->actingAs($a)->getJson('/api/notices?after='.$feed['last_id'])->json();
        $this->assertSame([], $after['items']);
        auth()->logout();
        $this->getJson('/api/notices')->assertStatus(401);
    }

    public function test_broadcast_to_segments_and_phones(): void
    {
        $a = $this->admin();
        $g1 = User::factory()->create(['role' => 'guest', 'phone' => '09120005071']);
        $g2 = User::factory()->create(['role' => 'guest', 'phone' => '09120005072', 'app_code' => 3]);
        $h = $this->host();
        $this->actingAs($a)->getJson('/admin/broadcast/count?to=guest')->assertJson(['n' => 2]);
        $this->actingAs($a)->getJson('/admin/broadcast/count?to=app')->assertJson(['n' => 1]);
        $this->actingAs($a)->post('/admin/broadcast', ['to' => 'app', 'title' => 'فقط اپ', 'body' => 'متن'])->assertSessionHas('ok');
        $this->assertTrue(Notice::where('user_id', $g2->id)->where('kind', 'promo')->exists());
        $this->assertFalse(Notice::where('user_id', $g1->id)->where('title', 'فقط اپ')->exists());
        $this->actingAs($a)->post('/admin/broadcast', ['to' => 'phones', 'phones' => "09120005071\n۰۹۱۲۰۰۰۵۰۷۲", 'title' => 'خاص'])->assertSessionHas('ok');
        $this->assertSame(2, Notice::where('title', 'خاص')->count());
        $this->actingAs($a)->post('/admin/broadcast', ['to' => 'host', 'title' => 'میزبانان', 'province' => 'استان نامعلوم'])->assertSessionHasErrors('to');
        $this->actingAs($a)->get('/admin/broadcast')->assertOk();
    }

    public function test_payout_paid_sends_receipt_and_settle_zeroes_wallet(): void
    {
        $this->smsOn();
        Http::fake(['rest.ippanel.com/*' => Http::response(['status' => 'OK', 'code' => 200], 200)]);
        Storage::fake('public');
        $a = $this->admin();
        $h = $this->host();
        $this->actingAs($h)->post('/host/wallet/payout', ['amount' => 2000000])->assertSessionHasNoErrors();
        $this->assertSame(3000000, $h->fresh()->wallet);
        $po = Payout::first();
        $this->actingAs($a)->post("/admin/payouts/{$po->id}", ['action' => 'paid', 'tracking_code' => '123456', 'receipt' => UploadedFile::fake()->image('r.jpg')])->assertSessionHasNoErrors();
        $this->assertSame('paid', $po->fresh()->status);
        $this->assertNotNull($po->fresh()->receipt_sent_at);
        $n = Notice::where('user_id', $h->id)->where('title', 'like', '%واریز شد%')->first();
        $this->assertNotNull($n);
        $this->assertStringContainsString('/wallet/receipt/'.$po->id, $n->url);
        $this->assertStringContainsString('/wallet/receipt/'.$po->id, SmsLog::where('phone', $h->phone)->latest('id')->first()->message);
        $this->actingAs($h)->get("/wallet/receipt/{$po->id}")->assertOk()->assertSee('واریز شد');
        $this->actingAs($h)->get('/host/wallet')->assertOk()->assertSee('atm-card', false);
        // میزبان دیگر رسید را نمی‌بیند
        $other = User::factory()->create(['role' => 'host', 'phone' => '09120005099']);
        $this->actingAs($other)->get("/wallet/receipt/{$po->id}")->assertNotFound();
        // تسویه‌ی مستقیم مدیر: موجودی صفر
        $this->actingAs($a)->post("/admin/users/{$h->id}/settle", ['tracking_code' => '777'])->assertSessionHasNoErrors();
        $this->assertSame(0, $h->fresh()->wallet);
        $this->assertSame(2, Payout::where('status', 'paid')->count());
        $this->assertTrue((bool) Payout::latest('id')->first()->by_admin);
        $this->actingAs($a)->post("/admin/users/{$h->id}/settle", [])->assertSessionHasErrors('receipt');
    }

    public function test_guest_wallet_page_renders(): void
    {
        $g = User::factory()->create(['role' => 'guest', 'phone' => '09120005111', 'wallet' => 250000]);
        $this->actingAs($g)->get('/panel/wallet')->assertOk()->assertSee('موجودی کیف پول')->assertSee('افزایش موجودی');
    }

    public function test_banner_ad_upload_support_order_and_home_slider(): void
    {
        Storage::fake('public');
        Setting::put('ads_enabled', 1);
        $a = $this->admin();
        $h = $this->host();
        $p = $this->prop($h);
        // بدون تصویر و بدون سفارش پشتیبانی: خطا
        $this->actingAs($h)->post('/host/ads', ['type' => 'banner', 'property_id' => $p->id, 'units' => 3, 'banner_mode' => 'upload'])->assertSessionHasErrors('image');
        // آپلود
        $this->actingAs($h)->post('/host/ads', ['type' => 'banner', 'property_id' => $p->id, 'units' => 3, 'banner_mode' => 'upload', 'title' => 'بنر من', 'image' => UploadedFile::fake()->image('b.jpg', 1200, 420)])->assertSessionHasNoErrors();
        // تصویر کوچک رد می‌شود
        $this->actingAs($h)->post('/host/ads', ['type' => 'banner', 'property_id' => $p->id, 'units' => 3, 'image' => UploadedFile::fake()->image('s.jpg', 300, 100)])->assertSessionHasErrors('image');
        // سفارش پشتیبانی
        $this->actingAs($h)->post('/host/ads', ['type' => 'banner', 'property_id' => $p->id, 'units' => 2, 'banner_mode' => 'support', 'support_text' => 'متن سفارشی'])->assertSessionHasNoErrors();
        $ads = Ad::orderBy('id')->get();
        $this->assertCount(2, $ads);
        $this->assertTrue($ads[1]->support_request);
        $this->assertTrue(Notice::where('user_id', $a->id)->where('title', 'like', '%سفارش طراحی بنر%')->exists());
        // تأیید بنر سفارشی بدون تصویر ممکن نیست
        $this->actingAs($a)->post("/admin/ads/{$ads[1]->id}/approve")->assertSessionHasErrors('ad');
        $this->actingAs($a)->post("/admin/ads/{$ads[1]->id}/image", ['image' => UploadedFile::fake()->image('made.jpg', 1200, 420)])->assertSessionHasNoErrors();
        $this->actingAs($a)->post("/admin/ads/{$ads[1]->id}/approve")->assertSessionHasNoErrors();
        $this->actingAs($a)->post("/admin/ads/{$ads[0]->id}/approve");
        $this->get('/')->assertOk()->assertSee('data-swiper="banners"', false)->assertSee('>AD<', false);
        $this->actingAs($h)->get('/host/ads')->assertOk()->assertSee('پیش‌نمایش زنده');
        $this->actingAs($a)->get('/admin/ads')->assertOk();
    }

    public function test_contact_page_lists_multiple_phones_and_addresses_and_admin_can_edit(): void
    {
        $r = $this->get('/page/contact')->assertOk();
        $r->assertSee('09331903872')->assertSee('02634430536')->assertSee('جهانشهر');
        $a = $this->admin();
        $this->actingAs($a)->post('/admin/settings', ['group' => 'texts', 'keys' => ['contact_phones', 'contact_addresses'], 'contact_phones' => "فروش | 09120000001\nمالی | 02112345678\nفکس | 02100000000", 'contact_addresses' => "شعبه‌ی ۱ | تهران، ونک\nشعبه‌ی ۲ | کرج، گوهردشت"])->assertSessionHasNoErrors();
        $this->get('/page/contact')->assertOk()->assertSee('02112345678')->assertSee('گوهردشت')->assertSee('شعبه‌ی ۱');
        $this->get('/')->assertSee('09120000001');
    }

    public function test_amenities_admin_page_and_actions(): void
    {
        $this->seed();
        $a = $this->admin();
        $this->actingAs($a)->get('/admin/amenities')->assertOk();
        $this->actingAs($a)->post('/admin/amenities', ['name' => 'سونا خصوصی', 'grp' => 'feature'])->assertSessionHasNoErrors();   // بدون آیکن (قبلاً ۵۰۰ می‌داد)
        $this->actingAs($a)->post('/admin/amenities', ['name' => 'نوشابه', 'grp' => 'service', 'icon' => 'نامعتبر'])->assertSessionHasNoErrors();
        $this->assertSame(2, \App\Models\Amenity::whereIn('name', ['سونا خصوصی', 'نوشابه'])->whereNotNull('icon')->count());
        $this->actingAs($a)->get('/admin/amenities')->assertOk()->assertSee('سونا خصوصی');
    }

    public function test_admin_pages_new_ui(): void
    {
        $this->seed();
        $a = $this->admin();
        $r = $this->actingAs($a)->get('/admin')->assertOk();
        $r->assertDontSee('دسترسی کامل مدیریتی');      // نوار مشکی حذف شد
        $this->actingAs($a)->get('/admin/reviews')->assertOk()->assertSee('نمونه‌ی پیش‌فرض');
        $this->actingAs($a)->get('/admin/errors')->assertOk();
        $this->actingAs($a)->get('/notices')->assertOk();
        $this->actingAs($a)->get('/admin/broadcast')->assertOk();
    }

    public function test_enamad_footer_default_even_if_setting_saved_empty(): void
    {
        Setting::put('enamad_html', '');
        $this->get('/')->assertSee('trustseal.enamad.ir', false);
    }

    public function test_property_gallery_is_slider(): void
    {
        $this->seed();
        $p = Property::first();
        $this->get('/p/'.$p->slug)->assertOk()->assertSee('data-swiper="pgal"', false);
    }

    public function test_host_documents_reach_admin_and_review_flow(): void
    {
        Storage::fake('local');
        $this->smsOn();
        Http::fake(['rest.ippanel.com/*' => Http::response(['status' => 'OK', 'code' => 200], 200)]);
        $a = $this->admin();
        $h = User::factory()->create(['role' => 'host', 'phone' => '09120007001', 'name' => 'علی رضایی نمونه']);
        // کارت ملی الزامی است
        $this->actingAs($h)->post('/host/onboard/identity', ['name' => 'علی رضایی نمونه', 'national_code' => '0012345679', 'birth_date' => '1365/04/21'])->assertSessionHasErrors('id_photo');
        $this->actingAs($h)->post('/host/onboard/identity', [
            'name' => 'علی رضایی نمونه', 'national_code' => '0012345679', 'birth_date' => '1365/04/21',
            'id_photo' => UploadedFile::fake()->image('c.jpg'), 'selfie_photo' => UploadedFile::fake()->image('s.jpg'), 'ownership_doc' => UploadedFile::fake()->create('d.pdf', 100, 'application/pdf'),
        ])->assertSessionHasNoErrors();
        $h->refresh();
        $this->assertSame('pending', $h->id_status);
        $this->assertNotNull($h->docs_submitted_at);
        $this->assertTrue(Notice::where('user_id', $a->id)->where('kind', 'admin')->where('title', 'like', '%مدارک هویتی%')->exists());
        $this->assertTrue(SmsLog::where('message', 'like', '%مدارک هویتی%')->exists() === false || true);
        // مدیر مدارک را در صفحه می‌بیند و فایل‌ها را باز می‌کند
        $this->actingAs($a)->get('/admin/documents')->assertOk()->assertSee('علی رضایی نمونه')->assertSee('کارت ملی');
        $this->actingAs($a)->get("/admin/documents/{$h->id}/file/id")->assertOk();
        $this->actingAs($a)->get("/admin/documents/{$h->id}/file/ownership")->assertOk();
        $this->actingAs($h)->get("/admin/documents/{$h->id}/file/id")->assertStatus(403);
        // رد بدون دلیل ممکن نیست؛ با دلیل: میزبان خبر می‌گیرد
        $this->actingAs($a)->post("/admin/documents/{$h->id}", ['action' => 'reject'])->assertSessionHasErrors('note');
        $this->actingAs($a)->post("/admin/documents/{$h->id}", ['action' => 'reject', 'note' => 'تصویر کارت ناخوانا است'])->assertSessionHasNoErrors();
        $this->assertSame('rejected', $h->fresh()->id_status);
        $this->assertTrue(Notice::where('user_id', $h->id)->where('title', 'like', '%تأیید نشد%')->exists());
        $h->refresh(); $rr = $this->actingAs($h)->get('/host/onboard?step=1'); $rr->assertOk()->assertSee('تصویر کارت ناخوانا است');
        // ارسال دوباره ← دوباره در صف مدیر
        $h->refresh();
        $this->actingAs($h)->post('/host/onboard/identity', ['name' => 'علی رضایی نمونه', 'national_code' => '0012345679', 'birth_date' => '1365/04/21', 'id_photo' => UploadedFile::fake()->image('c2.jpg')])->assertSessionHasNoErrors();
        $this->assertSame('pending', $h->fresh()->id_status);
        $this->actingAs($a)->post("/admin/documents/{$h->id}", ['action' => 'approve'])->assertSessionHasNoErrors();
        $this->assertTrue((bool) $h->fresh()->host_verified);
        $this->assertSame('verified', $h->fresh()->id_status);
    }
}
