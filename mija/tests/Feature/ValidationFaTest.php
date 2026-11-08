<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** همه‌ی پیام‌های خطا باید فارسی باشند و کلید ترجمه (validation.xxx) یا انگلیسی نشت نکند */
class ValidationFaTest extends TestCase
{
    use RefreshDatabase;

    private function assertPersianErrors($res): void
    {
        $res->assertSessionHasErrors();
        foreach (session('errors')->getBag('default')->all() as $m) {
            $this->assertStringNotContainsString('validation.', $m);
            $this->assertMatchesRegularExpression('/[\x{0600}-\x{06FF}]/u', $m, $m);
            $this->assertDoesNotMatchRegularExpression('/\b(field|must|required|invalid)\b/i', $m, $m);
        }
    }

    public function test_register_errors_are_persian_and_clickable(): void
    {
        $res = $this->from('/register')->post('/register', ['name' => 'a', 'phone' => '123', 'password' => '12', 'password_confirmation' => '99', 'role' => 'x']);
        $this->assertPersianErrors($res);
        $html = $this->followingRedirects()->from('/register')->post('/register', ['name' => 'a', 'phone' => '123', 'password' => '12', 'role' => 'guest'])->getContent();
        $this->assertStringContainsString('id="errbox"', $html);
        $this->assertStringContainsString('data-err-key="phone"', $html);
        $this->assertStringContainsString('شماره موبایل باید با', $html);
    }

    public function test_property_form_errors_are_persian(): void
    {
        $host = User::factory()->create(['role' => 'host', 'phone' => '09125550000', 'national_code' => '0012345679', 'sheba' => 'IR062960000000100324200001', 'card_number' => '6037997000001234', 'host_onboarded_at' => now()]);
        $res = $this->actingAs($host)->from('/host/properties/create')->post('/host/properties', [
            'title' => 'ab', 'capacity_base' => 5, 'capacity_max' => 3, 'price_weekday' => 100, 'min_nights' => 5, 'max_nights' => 2,
            'images' => [\Illuminate\Http\UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')],
        ]);
        $this->assertPersianErrors($res);
        $errs = session('errors')->getBag('default');
        $this->assertStringContainsString('نقشه', $errs->first('lat'));
        $this->assertStringContainsString('ظرفیت استاندارد', $errs->first('capacity_max'));
        $this->assertStringContainsString('عکس', $errs->first('images.0'));
    }

    public function test_error_pages_exist_in_persian(): void
    {
        $this->get('/nope-nope')->assertNotFound()->assertSee('پیدا نشد');
    }
}
