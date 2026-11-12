<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\SmsLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** هر رویدادِ نیازمند تأیید مدیر باید برای مدیرانِ فعال و شماره‌های اضافه پیامک بفرستد */
class AdminSmsAlertsTest extends TestCase
{
    use RefreshDatabase;

    private function setupSms(): void
    {
        Http::fake(['*' => Http::response(['status' => 'OK'], 200)]);
        foreach (['sms_enabled' => 1, 'sms_token' => str_repeat('a', 40), 'sms_originator' => '3000505', 'admin_sms_alerts' => 1, 'admin_sms_phones' => "09351112233\n+98 912 000 7788, 0935 111 2233"] as $k => $v) {
            Setting::put($k, $v);
        }
        User::factory()->create(['role' => 'admin', 'phone' => '09120000000', 'is_active' => true]);
        User::factory()->create(['role' => 'admin', 'phone' => '09120000001', 'is_active' => false]);
    }

    public function test_extra_phones_are_normalized_and_deduped(): void
    {
        $this->setupSms();
        $this->assertSame(['09351112233', '09120007788'], User::adminExtraPhones());
        $rows = User::adminSmsRecipients();
        $this->assertCount(4, $rows); // دو حساب مدیر + دو شماره‌ی اضافه
    }

    public function test_new_host_registration_sms_goes_to_active_admins_and_extras(): void
    {
        $this->setupSms();
        $this->post('/register', ['name' => 'علی میزبان', 'phone' => '09361234567', 'password' => 'secret123', 'password_confirmation' => 'secret123', 'role' => 'host'])->assertRedirect();
        $phones = SmsLog::pluck('phone')->all();
        $this->assertContains('09120000000', $phones);
        $this->assertContains('09351112233', $phones);
        $this->assertContains('09120007788', $phones);
        $this->assertNotContains('09120000001', $phones, 'مدیر غیرفعال پیامک نمی‌گیرد');
        $this->assertSame(1, SmsLog::where('phone', '09351112233')->count(), 'شماره‌ی تکراری فقط یک پیامک بگیرد');
        $this->assertStringContainsString('میزبان جدید', SmsLog::where('phone', '09120000000')->first()->message);
    }

    public function test_guest_registration_does_not_sms_admins(): void
    {
        $this->setupSms();
        $this->post('/register', ['name' => 'مهمان تازه', 'phone' => '09361234568', 'password' => 'secret123', 'password_confirmation' => 'secret123', 'role' => 'guest'])->assertRedirect();
        $this->assertSame(0, SmsLog::where('phone', '09120000000')->count());
    }

    public function test_switch_off_sends_nothing(): void
    {
        $this->setupSms();
        Setting::put('admin_sms_alerts', 0);
        User::notifyAdmins('تست', 'متن', null, true);
        $this->assertSame(0, SmsLog::count());
    }
}
