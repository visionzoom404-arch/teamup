<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** همه‌ی صفحه‌های اصلی هر نقش باید بدون خطا باز شوند */
class PagesSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        User::create(['name' => 'مدیر تست', 'phone' => '09130000000', 'password' => 'secret123', 'role' => 'admin']);
    }

    private function ok(User $u, array $urls): void
    {
        foreach ($urls as $url) {
            $res = $this->actingAs($u)->get($url);
            $this->assertTrue(in_array($res->getStatusCode(), [200, 302]), "$url → ".$res->getStatusCode().' '.mb_substr(strip_tags((string) $res->getContent()), 0, 300));
        }
    }

    public function test_public_pages(): void
    {
        $p = Property::first();
        foreach (['/', '/search', '/login', '/register', '/register?as=host', "/p/{$p->slug}", '/help', '/page/rules', '/page/rules-guest', '/page/rules-host', '/page/privacy', '/page/about', '/page/faq', '/page/contact', '/page/become-host'] as $url) {
            $res = $this->get($url);
            $this->assertSame(200, $res->getStatusCode(), "$url → ".$res->getStatusCode().' '.mb_substr(strip_tags((string) $res->getContent()), 0, 300));
        }
    }

    public function test_guest_pages(): void
    {
        $g = User::where('phone', '09123333333')->first();
        $b = $g->bookings()->first();
        $this->ok($g, ['/panel', '/panel/bookings', "/panel/bookings/{$b->code}", "/bookings/{$b->code}/invoice", '/panel/favorites', '/panel/wallet', '/notices', '/profile', "/u/{$g->id}"]);
    }

    public function test_host_pages(): void
    {
        $h = User::where('phone', '09121111111')->first();
        $p = $h->properties()->first();
        $b = $h->hostBookings()->first();
        $this->ok($h, ['/host', '/host/properties', '/host/properties/create', "/host/properties/{$p->slug}/edit", "/host/properties/{$p->slug}/pricing", "/host/properties/{$p->slug}/calendar", '/host/bookings', "/host/bookings/{$b->code}", "/bookings/{$b->code}/invoice",
            '/host/reviews', '/host/wallet', '/host/coupons', '/host/ads', '/host/onboard', '/host/onboard?step=2', "/host/guests/{$b->guest_id}", '/profile']);
    }

    public function test_admin_pages(): void
    {
        $a = User::where('role', 'admin')->first();
        $p = Property::first();
        $b = Booking::first();
        $u = User::where('role', 'host')->first();
        $this->ok($a, ['/admin', '/admin/properties', "/admin/properties/{$p->slug}", "/admin/properties/{$p->slug}/edit", "/admin/properties/{$p->slug}/pricing", '/admin/users', '/admin/users/create', "/admin/users/{$u->id}", '/admin/bookings', "/admin/bookings/{$b->code}",
            '/admin/payments', '/admin/payments?tab=review', '/admin/payouts', '/admin/reviews', '/admin/reviews?tab=all', '/admin/amenities', '/admin/coupons', '/admin/ads', '/admin/pages', '/admin/pages/rules', '/admin/words', '/admin/sms', '/admin/activity', '/admin/broadcast', '/admin/pool', '/admin/search?q=علی',
            '/admin/settings', '/admin/settings?tab=cash', '/admin/settings?tab=ads', '/admin/settings?tab=sms', '/admin/settings?tab=gateway', '/admin/settings?tab=texts', '/admin/settings?tab=peaks', '/admin/export/users', '/profile']);
    }
}
