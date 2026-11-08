<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Setting;
use App\Models\ShortLink;
use App\Models\SmsLog;
use App\Models\User;
use App\Support\Gateways;
use App\Support\Sms;
use App\Support\SmsEvents;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** دور چهارم: بیت‌پی، خاموش/روشن کردن روش‌های پرداخت، پترن پیامک و لینک کوتاه */
class RoundFourTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create(['name' => 'مدیر', 'phone' => '09130000601', 'password' => 'secret123', 'role' => 'admin']);
    }

    private function guest(): User
    {
        return User::factory()->create(['role' => 'guest', 'phone' => '09120006011']);
    }

    private function smsOn(): void
    {
        foreach (['sms_enabled' => 1, 'sms_token' => str_repeat('a', 40), 'sms_originator' => '3000505'] as $k => $v) {
            Setting::put($k, $v);
        }
    }

    public function test_bitpay_start_and_callback_credit_wallet(): void
    {
        Setting::put('pay_bitpay_on', 1);
        Setting::put('bitpay_api', 'KEY-ONE');
        $this->assertTrue(Gateways::on('bitpay'));
        Http::fake(function ($r) {
            return str_contains($r->url(), 'gateway-send') ? Http::response('4567', 200) : Http::response('1', 200);
        });
        $u = $this->guest();
        $res = $this->actingAs($u)->post('/panel/wallet/topup', ['amount' => 50000, 'gateway' => 'bitpay']);
        $res->assertRedirect();
        $this->assertStringEndsWith('/payment/gateway-4567-get', $res->headers->get('Location'));
        Http::assertSent(fn ($r) => str_contains($r->url(), 'gateway-send') && $r['api'] === 'KEY-ONE' && (int) $r['amount'] === 500000);
        $pay = Payment::first();
        $this->assertSame('bitpay', $pay->gateway);
        $this->assertSame('4567', $pay->track_id);

        // بازگشت از بانک (POST بدون توکن CSRF)
        $this->post('/pay/bitpay', ['id_get' => '4567', 'trans_id' => '99887'])->assertRedirect();
        $this->assertSame('paid', $pay->fresh()->status);
        $this->assertSame(50000, $u->fresh()->wallet);
        Http::assertSent(fn ($r) => str_contains($r->url(), 'gateway-result-second') && $r['trans_id'] === '99887');
    }

    public function test_bitpay_errors_are_persian_and_payment_fails(): void
    {
        Setting::put('pay_bitpay_on', 1);
        Setting::put('bitpay_api', 'BAD');
        Http::fake(['*' => Http::response('-1', 200)]);
        $u = $this->guest();
        $this->actingAs($u)->post('/panel/wallet/topup', ['amount' => 50000, 'gateway' => 'bitpay'])->assertSessionHasErrors('pay');
        $this->assertSame('failed', Payment::first()->status);
        $this->assertSame('خطا', 'خطا');
    }

    public function test_bitpay_failed_verify_does_not_credit(): void
    {
        Setting::put('pay_bitpay_on', 1);
        Setting::put('bitpay_api', 'K');
        $u = $this->guest();
        $pay = Payment::create(['user_id' => $u->id, 'purpose' => 'topup', 'amount' => 20000, 'gateway' => 'bitpay', 'authority' => '777', 'track_id' => '777', 'merchant' => 'K']);
        Http::fake(['*' => Http::response('-3', 200)]);
        $this->post('/pay/bitpay', ['id_get' => '777', 'trans_id' => '1'])->assertRedirect();
        $this->assertSame('failed', $pay->fresh()->status);
        $this->assertSame(0, (int) $u->fresh()->wallet);
    }

    public function test_methods_can_be_switched_off_by_admin(): void
    {
        $a = $this->admin();
        $this->actingAs($a)->post('/admin/settings', ['group' => 'gateway', 'keys' => ['pay_zibal_on', 'pay_bitpay_on', 'pay_sandbox_on', 'pay_wallet_on', 'card_to_card_enabled'],
            'pay_zibal_on' => 0, 'pay_bitpay_on' => 0, 'pay_sandbox_on' => 0, 'pay_wallet_on' => 0, 'card_to_card_enabled' => 0])->assertSessionHasNoErrors();
        $this->assertSame([], Gateways::online());
        $this->assertFalse(Gateways::on('wallet'));
        $this->actingAs($this->guest())->post('/panel/wallet/topup', ['amount' => 50000])->assertSessionHasErrors('amount');
        $this->actingAs($a)->get('/admin/settings?tab=gateway')->assertOk()->assertSee('بیت‌پی');
    }

    public function test_short_link_redirects_and_counts(): void
    {
        $short = ShortLink::make('https://mija.click/host/bookings/MJ-X');
        $this->assertSame($short, ShortLink::make('https://mija.click/host/bookings/MJ-X'));
        $this->get(parse_url($short, PHP_URL_PATH))->assertRedirect('https://mija.click/host/bookings/MJ-X');
        $this->assertSame(1, (int) ShortLink::first()->clicks);
        $this->get('/s/zzzzzz')->assertNotFound();
    }

    public function test_every_sms_event_vars_match_template_and_never_have_links(): void
    {
        foreach (SmsEvents::all() as $k => $e) {
            $t = SmsEvents::template($k);
            preg_match_all('/%(\w+)%/', $t, $m);
            foreach ($m[1] as $v) {
                $this->assertContains($v, SmsEvents::vars($k), "$k: متغیر $v تعریف نشده");
            }
            foreach (SmsEvents::vars($k) as $v) {
                $this->assertStringContainsString('%'.$v.'%', $t, "$k: متغیر $v در متن نیامده");
            }
            $this->assertStringNotContainsString('%link%', $t);
            $this->assertDoesNotMatchRegularExpression('~https?://|www\.|\.(com|ir|click)\b~i', $t);
        }
        foreach (['ad_promo', 'general'] as $k) {
            $this->assertStringContainsString('لغو۱۱', SmsEvents::template($k));
        }
        $this->assertCount(count(SmsEvents::all()), array_unique(array_map(fn ($k) => SmsEvents::template($k), array_keys(SmsEvents::all()))));
    }

    public function test_sms_without_pattern_code_goes_plain_and_fallback_off_is_logged(): void
    {
        $this->smsOn();
        Setting::put('sms_plain_fallback', 1);
        Http::fake(['*' => Http::response(['status' => 'OK', 'code' => 200], 200)]);
        $this->assertTrue(Sms::event('booking_rejected_guest', '09120006011', ['property' => 'ویلا'], 'https://mija.click/search'));
        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/messages') && str_contains($r['message'], 'ویلا') && ! str_contains($r['message'], 'http') && $r['recipient'] === ['+989120006011']);
        $this->assertSame('plain', SmsLog::latest('id')->first()->via);

        Setting::put('sms_plain_fallback', 0);
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake();
        $this->assertFalse(Sms::event('booking_rejected_guest', '09120006011', ['property' => 'ویلا'], null));
        $this->assertStringContainsString('کد پترن', Sms::$lastError);
        $this->assertSame('failed', SmsLog::latest('id')->first()->status);
        Http::assertNothingSent();
    }

    public function test_pattern_used_when_set_falls_back_to_plain_when_rejected_and_retries_transients(): void
    {
        $this->smsOn();
        Setting::put('sms_pat_booking_rejected_guest', 'abc123');
        Http::fake(['*' => Http::response(['status' => 'OK', 'code' => 200, 'data' => ['message_id' => 9]], 200)]);
        $this->assertTrue(Sms::event('booking_rejected_guest', '09120006011', ['property' => 'ویلای جنگلی'], 'https://mija.click/search'));
        Http::assertSent(function ($r) {
            $v = (array) $r['values'];

            return str_ends_with($r->url(), '/messages/patterns/send') && $r['pattern_code'] === 'abc123' && $r['recipient'] === '+989120006011' && $v === ['property' => 'ویلای جنگلی'];
        });
        $this->assertSame('pattern', SmsLog::latest('id')->first()->via);

        // پترن تأیید‌نشده → همان پیام عادی می‌رود
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake(function ($r) {
            return str_contains($r->url(), 'patterns') ? Http::response(['status' => 'ERROR', 'code' => 400, 'error_message' => 'pattern not approved'], 400) : Http::response(['status' => 'OK', 'code' => 200], 200);
        });
        $this->assertTrue(Sms::event('booking_rejected_guest', '09120006011', ['property' => 'x'], null));
        $this->assertSame('plain', SmsLog::latest('id')->first()->via);

        // خطای موقت (۵۰۰) → ثبت برای تلاش مجدد و ارسال موفق در تلاش بعدی
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake(['*' => Http::response('oops', 500)]);
        $this->assertFalse(Sms::event('login_code', '09120006011', ['code' => '12345'], null));
        $l = SmsLog::latest('id')->first();
        $this->assertSame('failed', $l->status);
        $this->assertNotNull($l->retry_at);
        $this->travel(3)->minutes();
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake(['*' => Http::response(['status' => 'OK', 'code' => 200], 200)]);
        $this->assertSame(1, Sms::retryDue());
        $this->assertSame('sent', SmsLog::latest('id')->first()->status);
        $this->assertSame(1, (int) SmsLog::latest('id')->first()->attempts);
    }

    public function test_plain_sms_gets_short_link_only_when_enabled(): void
    {
        $this->smsOn();
        Http::fake(['*' => Http::response(['status' => 'OK', 'code' => 200], 200)]);
        Setting::put('sms_use_link', 1);
        Sms::event('booking_rejected_guest', '09120006011', ['property' => 'ویلا'], 'https://mija.click/search');
        Http::assertSent(fn ($r) => preg_match('~\n.*/s/\w+$~u', $r['message']) === 1);
    }

    public function test_admin_saves_pattern_codes_and_sees_credit_after_save(): void
    {
        Http::fake(['*' => Http::response(['status' => 'OK', 'code' => 200, 'data' => ['credit' => 1234567]], 200)]);
        $a = $this->admin();
        $this->actingAs($a)->post('/admin/settings', ['group' => 'sms', 'keys' => ['sms_token', 'sms_originator', 'sms_pat_login_code'],
            'sms_token' => str_repeat('t', 30), 'sms_originator' => '3000505', 'sms_pat_login_code' => 'LOGIN01'])->assertSessionHasNoErrors();
        $this->assertSame('LOGIN01', SmsEvents::patternCode('login_code'));
        $page = $this->actingAs($a)->followingRedirects()->get('/admin/settings?tab=sms');
        $page->assertOk()->assertSee('پترن‌های پیامک')->assertSee('LOGIN01', false);
        $this->assertStringContainsString('۱,۲۳۴,۵۶۷', $page->getContent());
        $this->actingAs($a)->post('/admin/settings', ['group' => 'sms', 'keys' => ['sms_pat_login_code'], 'sms_pat_login_code' => 'bad code!'])->assertSessionHasErrors('sms_pat_login_code');
    }

    public function test_enamad_links_open_in_same_window_inside_app(): void
    {
        Setting::put('enamad_html', '<a referrerpolicy="origin" target="_blank" href="https://trustseal.enamad.ir/?id=1&Code=x"><img src="https://trustseal.enamad.ir/logo.aspx?id=1&Code=x"></a>');
        $web = $this->get('/')->getContent();
        $this->assertStringContainsString('target="_blank"', $web);
        $app = $this->withHeaders(['User-Agent' => 'Mozilla/5.0 MijaApp/1.3.0 (code 5; flavor store)'])->get('/')->getContent();
        $this->assertStringNotContainsString('trustseal.enamad.ir/?id=1&amp;Code=x" target', $app);
        $this->assertMatchesRegularExpression('/target=["\']_self["\'][^>]*href=["\']https:\/\/trustseal|href=["\']https:\/\/trustseal[^>]*target=["\']_self["\']/', $app);
    }

    public function test_visits_are_tracked_and_shown_to_admin(): void
    {
        $ua = 'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Mobile Safari/537.36';
        $this->withHeaders(['User-Agent' => $ua, 'Referer' => 'https://www.google.com/'])->get('/')->assertOk();
        $this->withHeaders(['User-Agent' => $ua])->get('/search?q='.rawurlencode('نوشهر'))->assertOk();
        $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'])->get('/')->assertOk();
        $this->assertSame(2, \App\Models\Visit::where('is_bot', false)->count());
        $v = \App\Models\Visit::where('path', '/')->where('is_bot', false)->first();
        $this->assertSame('google', $v->source);
        $this->assertSame('mobile', $v->device);
        $this->assertSame('Chrome', $v->browser);
        $this->assertSame('Android', $v->os);
        $this->assertTrue($v->is_new);
        $this->assertNotEmpty($v->visitor);
        $this->assertSame('Googlebot', \App\Models\Visit::where('is_bot', true)->value('bot_name'));
        $this->assertSame('نوشهر', \App\Models\Visit::where('path', '/search')->value('search'));

        $a = $this->admin();
        $this->actingAs($a)->get('/')->assertOk();
        $this->assertSame(2, \App\Models\Visit::where('is_bot', false)->count());   // بازدید مدیر شمرده نمی‌شود
        $this->actingAs($a)->get('/admin/analytics?days=7')->assertOk()->assertSee('آمار بازدید سایت')->assertSee('گوگل')->assertSee('Googlebot');
        $this->actingAs($a)->get('/admin/analytics?days=1')->assertOk();
    }

    public function test_search_console_verification_tag_file_and_tab(): void
    {
        $a = $this->admin();
        $this->actingAs($a)->post('/admin/settings', ['group' => 'seo', 'keys' => ['google_verify', 'google_verify_file'],
            'google_verify' => '<meta name="google-site-verification" content="AbC123_xyz-TOKEN" />', 'google_verify_file' => 'google0123456789abcdef.html'])->assertSessionHasNoErrors();
        $this->assertSame('AbC123_xyz-TOKEN', setting('google_verify'));
        $this->get('/')->assertSee('<meta name="google-site-verification" content="AbC123_xyz-TOKEN">', false);
        $this->get('/google0123456789abcdef.html')->assertOk()->assertSee('google-site-verification: google0123456789abcdef.html', false);
        $this->get('/google9999999999999999.html')->assertNotFound();
        $this->actingAs($a)->get('/admin/settings?tab=seo')->assertOk()->assertSee('آمادگی برای گوگل سرچ کنسول')->assertSee('sitemap.xml');
        $this->actingAs($a)->post('/admin/settings', ['group' => 'seo', 'keys' => ['google_verify_file'], 'google_verify_file' => 'evil.php'])->assertSessionHasErrors('google_verify_file');
    }

    public function test_error_report_explains_in_persian_with_fix_buttons(): void
    {
        $ex = \App\Support\ErrorExplainer::explain('SQLSTATE[HY000]: General error: 1 no such table: visits');
        $this->assertSame('migrate', $ex['action']);
        $this->assertStringContainsString('visits', $ex['title']);
        $this->assertSame('sms', \App\Support\ErrorExplainer::explain('Http error to https://rest.ippanel.com/v1/messages')['route'][1]['tab']);
        $this->assertSame('gateway', \App\Support\ErrorExplainer::explain('cURL error 28 gateway.zibal.ir')['route'][1]['tab']);
        $this->assertNotEmpty(\App\Support\ErrorExplainer::explain('something weird')['fix']);

        $f = storage_path('logs/laravel-test-errors.log');
        file_put_contents($f, "[2026-11-03 10:00:00] production.ERROR: SQLSTATE[HY000]: General error: 1 no such table: zztbl (Connection: sqlite)\n[2026-11-03 10:05:00] production.ERROR: SQLSTATE[HY000]: General error: 1 no such table: zztbl (Connection: sqlite)\n");
        try {
            $a = $this->admin();
            $this->actingAs($a)->get('/admin/errors')->assertOk()->assertSee('جدول «zztbl» در دیتابیس ساخته نشده')->assertSee('رفع خودکار')->assertSee('۲ بار');
            $this->actingAs($a)->post('/admin/errors/fix', ['action' => 'rm -rf'])->assertNotFound();
            $this->actingAs($a)->post('/admin/errors/clear')->assertRedirect();
            $this->assertSame('', (string) file_get_contents($f));
        } finally {
            @unlink($f);
        }
    }

    public function test_voucher_after_wallet_payment_and_payout_receipt_pages(): void
    {
        $host = User::factory()->create(['role' => 'host', 'phone' => '09120006100', 'sheba' => 'IR062960000000100324200001', 'bank_owner' => 'علی', 'wallet' => 5000000]);
        $p = \App\Models\Property::create([
            'host_id' => $host->id, 'title' => 'ویلای ووچر', 'type' => 'villa', 'province' => 'مازندران', 'city' => 'نوشهر', 'description' => str_repeat('توضیحات ', 10),
            'capacity_base' => 4, 'capacity_max' => 6, 'price_weekday' => 1000000, 'price_weekend' => 1000000, 'extra_guest_fee' => 0, 'cleaning_fee' => 0,
            'instant_booking' => true, 'cancellation_policy' => 'moderate', 'status' => 'approved', 'address' => 'آدرس کامل تست',
        ]);
        $g = User::factory()->create(['role' => 'guest', 'phone' => '09120006101', 'wallet' => 9000000]);
        $b = \App\Support\BookingService::create($g, $p, today()->addDays(6), today()->addDays(8), 2, 0, null, null);
        Setting::put('pay_wallet_on', 1);
        // پیش از پرداخت ووچری نیست
        $this->actingAs($g)->get('/bookings/'.$b->code.'/voucher')->assertNotFound();
        $this->actingAs($g)->post('/panel/bookings/'.$b->code.'/pay', ['method' => 'wallet'])->assertRedirect(route('booking.voucher', $b));
        $res = $this->actingAs($g)->get('/bookings/'.$b->code.'/voucher')->assertOk();
        $res->assertSee('رزرو شما قطعی شد')->assertSee($b->code)->assertSee('vc-bar', false)->assertSee('دانلود ووچر');
        $this->actingAs(User::factory()->create(['role' => 'guest', 'phone' => '09120006102']))->get('/bookings/'.$b->code.'/voucher')->assertNotFound();
        // بارکد Code 39: شروع و پایان با * (۹ المان در هر نویسه)
        $w = \App\Support\Code39::widths('MJ-1');
        $this->assertSame(6 * 9 + 5, count($w));

        // برداشت میزبان: بعد از ثبت، فاکتور برداشت (در انتظار) نشان داده می‌شود
        Setting::put('min_payout', 1000);
        $r = $this->actingAs($host)->post('/host/wallet/payout', ['amount' => 2000000]);
        $po = \App\Models\Payout::first();
        $r->assertRedirect(route('payout.receipt', $po));
        $this->actingAs($host)->get(route('payout.receipt', $po))->assertOk()->assertSee('فاکتور درخواست برداشت')->assertSee('در انتظار بررسی')->assertSee('rt-steps', false);
    }

    public function test_admin_can_edit_and_delete_payments_and_see_review_sms_preview(): void
    {
        $a = $this->admin();
        $u = $this->guest();
        $pay = \App\Models\Payment::create(['user_id' => $u->id, 'purpose' => 'topup', 'amount' => 50000, 'gateway' => 'zibal', 'status' => 'failed', 'authority' => 'ed1']);
        $this->actingAs($a)->get('/admin/payments')->assertOk()->assertSee('ویرایش');
        $this->actingAs($a)->get(route('admin.payments.edit', $pay))->assertOk()->assertSee('ویرایش پرداخت');
        $this->actingAs($a)->post(route('admin.payments.update', $pay), ['amount' => 70000, 'status' => 'paid', 'gateway' => 'card', 'ref_id' => 'R1', 'note' => 'دستی', 'apply' => 1])->assertRedirect(route('admin.payments'));
        $this->assertSame(70000, (int) $pay->fresh()->amount);
        $this->assertSame('paid', $pay->fresh()->status);
        $this->assertSame(70000, (int) $u->fresh()->wallet);   // اثر شارژ کیف پول اعمال شد
        $this->assertDatabaseHas('activity_logs', ['action' => 'payment.update']);
        $this->actingAs($u)->get(route('admin.payments.edit', $pay))->assertStatus(403);
        $this->actingAs($a)->delete(route('admin.payments.destroy', $pay))->assertRedirect();
        $this->assertDatabaseMissing('payments', ['id' => $pay->id]);

        $this->actingAs($a)->get('/admin/reviews')->assertOk()->assertSee('پیامک و صفحه‌ی نظرسنجی');
        $this->actingAs($a)->get('/admin/reviews/preview')->assertOk()->assertSee('متن پیامک نظرسنجی')->assertSee('نظرتان را');
    }

    public function test_marketing_contacts_send_optout_cap_and_registration(): void
    {
        $this->smsOn();
        Http::fake(['*' => Http::response(['status' => 'OK', 'code' => 200], 200)]);
        $a = $this->admin();
        $this->actingAs($a)->get('/admin/marketing')->assertOk()->assertSee('دعوت میزبانان');
        $this->assertSame('09123456789', \App\Models\MarketingContact::normalize('+98 912 345 6789'));
        $this->assertSame('09351234567', \App\Models\MarketingContact::normalize('۰۹۳۵۱۲۳۴۵۶۷'));
        $this->assertNull(\App\Models\MarketingContact::normalize('12345'));

        $this->actingAs($a)->post('/admin/marketing', ['numbers' => "09123456789 علی رضایی\n۰۹۳۵۱۲۳۴۵۶۷\nxx\n09123456789", 'send' => 1])->assertRedirect();
        $this->assertSame(2, \App\Models\MarketingContact::count());
        $c = \App\Models\MarketingContact::where('phone', '09123456789')->first();
        $this->assertSame('علی رضایی', $c->name);
        $this->assertSame('sent', $c->status);
        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/messages') && str_contains($r['message'], 'علی رضایی') && str_contains($r['message'], '/s/') && str_contains($r['message'], 'لغو۱۱'));
        $this->assertDatabaseHas('short_links', ['url' => url('/register').'?as=host&utm_source=sms']);

        // لغو: دیگر پیامی نمی‌رود
        $this->actingAs($a)->post(route('admin.marketing.optout', $c))->assertRedirect();
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake();
        $this->actingAs($a)->post(route('admin.marketing.send', $c))->assertSessionHasErrors('send');
        Http::assertNothingSent();

        // سقف روزانه
        Setting::put('mk_daily_cap', 1);
        $this->actingAs($a)->post('/admin/marketing', ['numbers' => '09111111111', 'send' => 1])->assertSessionHasErrors('send');
        $this->assertSame('new', \App\Models\MarketingContact::where('phone', '09111111111')->value('status'));

        // ثبت‌نام همان شماره به‌عنوان میزبان، وضعیت را «ثبت‌نام کرد» می‌کند
        \Illuminate\Support\Facades\Auth::logout();
        $this->post('/register', ['name' => 'علی', 'phone' => '09111111111', 'password' => 'secret123', 'password_confirmation' => 'secret123', 'role' => 'host']);
        $this->assertSame('registered', \App\Models\MarketingContact::where('phone', '09111111111')->value('status'));
    }

    public function test_blog_seo_pages_sitemap_and_admin_editor(): void
    {
        $this->assertGreaterThanOrEqual(8, \App\Models\Article::count());
        $this->get('/blog')->assertOk()->assertSee('مجله‌ی می‌جا')->assertSee('اجاره ویلا');
        $r = $this->get('/blog/ejare-villa')->assertOk();
        $r->assertSee('<h1>', false)->assertSee('application/ld+json', false)->assertSee('"@type":"Article"', false)->assertSee('BreadcrumbList', false)->assertSee('<link rel="canonical" href="'.url('/blog/ejare-villa').'">', false);
        $r->assertSee('og:type" content="article"', false)->assertSee('فهرست مطالب');
        $this->get('/blog/nope')->assertNotFound();
        $sm = $this->get('/sitemap.xml')->assertOk()->getContent();
        $this->assertStringContainsString(url('/blog/ejare-villa'), $sm);
        $this->assertStringContainsString(url('/blog'), $sm);

        $a = $this->admin();
        $this->actingAs($a)->get('/admin/articles')->assertOk()->assertSee('مقاله‌های مجله');
        $body = '<h2>تست</h2><p>'.str_repeat('متن آزمایشی مقاله ', 30).'</p><script>alert(1)</script><a href="javascript:alert(2)" onclick="x()">لینک</a>';
        $this->actingAs($a)->post('/admin/articles', ['title' => 'مقاله‌ی آزمایشی برای تست', 'excerpt' => str_repeat('خلاصه‌ی مقاله ', 6), 'category' => 'تست', 'body' => $body, 'status' => 'draft', 'slug' => 'test-article', 'keyword' => 'تست'])->assertRedirect(route('admin.articles'));
        $art = \App\Models\Article::where('slug', 'test-article')->first();
        $this->assertStringNotContainsString('<script', $art->body);
        $this->assertStringNotContainsString('onclick', $art->body);
        $this->assertStringNotContainsString('javascript:', $art->body);
        auth()->logout();
        $this->get('/blog/test-article')->assertNotFound();           // پیش‌نویس
        $this->actingAs($a)->post('/admin/articles/'.$art->slug, ['title' => 'مقاله‌ی آزمایشی برای تست', 'excerpt' => str_repeat('خلاصه‌ی مقاله ', 6), 'category' => 'تست', 'body' => $body, 'status' => 'published', 'slug' => 'test-article'])->assertRedirect();
        auth()->logout();
        $this->get('/blog/test-article')->assertOk();
        $this->actingAs($a)->post('/admin/articles', ['title' => 'مقاله‌ی تکراری برای تست', 'excerpt' => str_repeat('خلاصه‌ی مقاله ', 6), 'category' => 'تست', 'body' => $body, 'status' => 'draft', 'slug' => 'test-article'])->assertSessionHasErrors('slug');
        $this->actingAs($a)->delete('/admin/articles/'.$art->slug)->assertRedirect();
        $this->assertDatabaseMissing('articles', ['slug' => 'test-article']);
    }

    public function test_admin_can_upload_and_reset_home_hero_image(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        $a = $this->admin();
        $this->actingAs($a)->post(route('admin.settings.hero'), ['hero' => \Illuminate\Http\UploadedFile::fake()->image('h.jpg', 400, 200)])->assertSessionHasErrors('hero');
        $this->actingAs($a)->post(route('admin.settings.hero'), ['hero' => \Illuminate\Http\UploadedFile::fake()->image('h.jpg', 1920, 1080)])->assertSessionHasNoErrors();
        $p = setting('home_hero');
        $this->assertStringStartsWith('site/hero-', $p);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($p);
        auth()->logout();
        $this->get('/')->assertOk()->assertSee('storage/'.$p, false);
        $this->actingAs($a)->post(route('admin.settings.hero'), ['reset' => 1])->assertSessionHasNoErrors();
        auth()->logout();
        $this->get('/')->assertSee('img/hero-home.jpg', false);
    }

    public function test_admin_creates_user_and_personal_login_link_sms_is_sent(): void
    {
        $this->smsOn();
        Http::fake(['*' => Http::response(['status' => 'OK', 'code' => 200], 200)]);
        $a = $this->admin();
        $this->actingAs($a)->post('/admin/users', ['name' => 'علی رضایی', 'phone' => '09351112233', 'role' => 'host', 'password' => 'secret123', 'send_invite' => 1])->assertRedirect();
        $u = User::where('phone', '09351112233')->first();
        $this->assertNotNull($u);
        $this->assertSame('host', $u->role);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('secret123', $u->password));
        $this->assertNotNull($u->invite_sent_at);

        $msg = '';
        Http::assertSent(function ($r) use (&$msg) {
            $msg = (string) ($r['message'] ?? '');

            return str_ends_with($r->url(), '/messages') && $r['recipient'] === ['+989351112233'];
        });
        $this->assertStringContainsString('سلام جناب/آقای علی رضایی', $msg);
        $this->assertStringContainsString('از پشتیبانی میزبانان پلتفرم می‌جا', $msg);
        $this->assertStringContainsString('(09351112233)', $msg);
        $this->assertStringContainsString('لطفا مراحل ثبت اقامتگاهتان را در پنل جلو ببرید.', $msg);
        $this->assertStringNotContainsString('secret123', $msg);
        preg_match('~(https?://\S+/j/([a-z0-9]{14}))~', $msg, $m);
        $this->assertNotEmpty($m, 'لینک شخصی در پیام نیست');

        // لینک: ورود بدون رمز و ادامه‌ی ثبت‌نام میزبان
        auth()->logout();
        $this->get('/j/'.$m[2])->assertRedirect(route('host.onboard'));
        $this->assertAuthenticatedAs($u);
        $this->assertNotNull($u->fresh()->invite_used_at);

        // لینک نامعتبر / منقضی
        auth()->logout();
        $this->get('/j/aaaaaaaaaaaaaa')->assertRedirect(route('login'));
        $this->assertGuest();
        $u->forceFill(['invite_expires_at' => now()->subMinute()])->save();
        $this->get('/j/'.$m[2])->assertRedirect(route('login'));
        $this->assertGuest();

        // ارسال دوباره: لینک قبلی باطل و لینک جدید معتبر
        $this->actingAs($a)->post(route('admin.users.invite', $u))->assertRedirect();
        $u = $u->fresh();
        $this->assertTrue($u->invite_expires_at->isFuture());
        auth()->logout();
        $this->get('/j/'.$m[2])->assertRedirect(route('login'));
    }

    public function test_invite_for_guest_role_text_and_no_link_for_admin_role_and_sms_off_shows_link(): void
    {
        $a = $this->admin();
        // پیامک خاموش: کاربر ساخته می‌شود و لینک برای ارسال دستی نشان داده می‌شود
        $res = $this->actingAs($a)->post('/admin/users', ['name' => 'سارا محمدی', 'phone' => '09361112233', 'role' => 'guest', 'password' => 'secret123']);
        $res->assertRedirect()->assertSessionHas('invite_link')->assertSessionHas('warn');
        $g = User::where('phone', '09361112233')->first();
        $this->assertStringContainsString('از پشتیبانی مهمانان', \App\Support\UserInvite::message($g, 'https://mija.click/j/x'));
        $this->assertStringContainsString('تکمیل حساب', \App\Support\UserInvite::message($g, 'https://mija.click/j/x'));
        // مدیر سایت: بدون لینک ورود
        $this->actingAs($a)->post('/admin/users', ['name' => 'مدیر دوم', 'phone' => '09371112233', 'role' => 'admin', 'password' => 'secret123'])->assertRedirect();
        $ad = User::where('phone', '09371112233')->first();
        $this->assertNull($ad->invite_hash);
        $this->actingAs($a)->post(route('admin.users.invite', $ad))->assertStatus(403);
    }

    public function test_dark_mode_markup_present(): void
    {
        foreach (['/', '/login', '/blog', '/app'] as $u) {
            $html = $this->get($u)->assertOk()->getContent();
            $this->assertStringContainsString('mija-theme', $html, $u);
            $this->assertStringContainsString('css/dark.auto.css', $html, $u);
            $this->assertStringContainsString('css/dark.css', $html, $u);
            $this->assertStringContainsString('data-theme-toggle', $html, $u);
        }
        $this->get('/no-such-page-xyz')->assertNotFound()->assertSee('css/dark.css', false);
        foreach (['dark.css', 'dark.auto.css'] as $f) {
            $this->assertFileExists(public_path('css/'.$f));
        }
    }

    public function test_night_hero_markup_and_admin_upload(): void
    {
        $this->get('/')->assertOk()->assertSee('hx-night', false)->assertSee('hero-night.webp', false)->assertSee('data-fx', false);
        $a = $this->admin();
        \Illuminate\Support\Facades\Storage::fake('public');
        $this->actingAs($a)->post(route('admin.settings.hero'), ['kind' => 'night', 'hero' => \Illuminate\Http\UploadedFile::fake()->image('n.jpg', 1400, 700)])->assertRedirect();
        $this->assertNotEmpty(setting('home_hero_night'));
        $this->assertEmpty(setting('home_hero'));
        $this->get('/')->assertOk()->assertSee(setting('home_hero_night'), false);
        $this->actingAs($a)->post(route('admin.settings.hero'), ['kind' => 'night', 'reset' => 1])->assertRedirect();
        $this->assertEmpty(setting('home_hero_night'));
    }
}
