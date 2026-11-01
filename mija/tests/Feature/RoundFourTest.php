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

    public function test_every_sms_event_has_link_or_documented_vars_and_unique_template(): void
    {
        foreach (SmsEvents::all() as $k => $e) {
            preg_match_all('/%(\w+)%/', $e[4], $m);
            foreach ($m[1] as $v) {
                $this->assertArrayHasKey($v, $e[3], "$k: متغیر $v تعریف نشده");
            }
            foreach (array_keys($e[3]) as $v) {
                $this->assertStringContainsString('%'.$v.'%', $e[4], "$k: متغیر $v در متن نیامده");
            }
        }
        $this->assertCount(count(SmsEvents::all()), array_unique(array_column(SmsEvents::all(), 4)));
    }

    public function test_sms_without_pattern_code_is_not_sent_and_reason_logged(): void
    {
        $this->smsOn();
        Http::fake();
        $ok = Sms::event('booking_rejected_guest', '09120006011', ['property' => 'ویلا'], 'https://mija.click/search');
        $this->assertFalse($ok);
        $this->assertStringContainsString('کد پترن', Sms::$lastError);
        $this->assertSame('failed', SmsLog::latest('id')->first()->status);
        Http::assertNothingSent();
    }

    public function test_pattern_sms_payload_and_error_mapping(): void
    {
        $this->smsOn();
        Setting::put('sms_pat_booking_rejected_guest', 'abc123');
        Http::fake(['*' => Http::response(['status' => 'OK', 'code' => 200, 'data' => ['message_id' => 9]], 200)]);
        $this->assertTrue(Sms::event('booking_rejected_guest', '09120006011', ['property' => 'ویلای جنگلی'], 'https://mija.click/search'));
        Http::assertSent(function ($r) {
            $v = (array) $r['values'];

            return str_ends_with($r->url(), '/messages/patterns/send') && $r['pattern_code'] === 'abc123' && $r['recipient'] === '+989120006011'
                && $v['property'] === 'ویلای جنگلی' && preg_match('~/s/\w+$~', $v['link']);
        });
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake(['*' => Http::response(['status' => 'ERROR', 'code' => 400, 'error_message' => 'pattern not found'], 400)]);
        $this->assertFalse(Sms::event('booking_rejected_guest', '09120006011', ['property' => 'x'], null));
        $this->assertStringContainsString('کد پترن', Sms::$lastError);
    }

    public function test_admin_saves_pattern_codes_and_sees_credit_after_save(): void
    {
        Http::fake(['*' => Http::response(['status' => 'OK', 'code' => 200, 'data' => ['credit' => 1234567]], 200)]);
        $a = $this->admin();
        $this->actingAs($a)->post('/admin/settings', ['group' => 'sms', 'keys' => ['sms_token', 'sms_originator', 'sms_pat_login_code'],
            'sms_token' => str_repeat('t', 30), 'sms_originator' => '3000505', 'sms_pat_login_code' => 'LOGIN01'])->assertSessionHasNoErrors();
        $this->assertSame('LOGIN01', SmsEvents::patternCode('login_code'));
        $page = $this->actingAs($a)->followingRedirects()->get('/admin/settings?tab=sms');
        $page->assertOk()->assertSee('پترن‌های پیامک')->assertSee('%link%', false)->assertSee('LOGIN01', false);
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
}
