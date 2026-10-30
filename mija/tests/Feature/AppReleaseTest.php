<?php

namespace Tests\Feature;

use App\Models\AppRelease;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/** صفحه‌ی دانلود، API نسخه، دانلود APK و مدیریت نسخه‌ها */
class AppReleaseTest extends TestCase
{
    use RefreshDatabase;

    private string $bak;

    protected function setUp(): void
    {
        parent::setUp();
        // پوشه‌ی APK را برای تست ایزوله می‌کنیم تا فایل‌های واقعی دست نخورند
        $this->bak = storage_path('app/apk_bak_'.uniqid());
        if (is_dir(AppRelease::dir())) {
            rename(AppRelease::dir(), $this->bak);
        }
        File::ensureDirectoryExists(AppRelease::dir());
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(AppRelease::dir());
        if (is_dir($this->bak)) {
            rename($this->bak, AppRelease::dir());
        }
        parent::tearDown();
    }

    private function fakeApk(string $name): UploadedFile
    {
        $tmp = tempnam(sys_get_temp_dir(), 'apk');
        file_put_contents($tmp, "PK\x03\x04".str_repeat('x', 2048));

        return new UploadedFile($tmp, $name, 'application/vnd.android.package-archive', null, true);
    }

    public function test_pages_without_release(): void
    {
        $this->get('/app')->assertOk()->assertSee('به‌زودی');
        $this->get('/app/update')->assertOk();
        $this->get('/app/download')->assertNotFound();
        $this->getJson('/api/app/version?code=1')->assertJson(['ok' => false]);
    }

    private function writeRelease(int $code, string $name, array $extra = []): void
    {
        $f = "mija-direct-$name.apk";
        file_put_contents(AppRelease::dir()."/$f", "PK\x03\x04".str_repeat('x', 2048 + $code));
        file_put_contents(AppRelease::dir().'/release.json', json_encode([
            'version_name' => $name, 'version_code' => $code, 'changelog' => ['مورد اول', 'مورد دوم'],
            'direct' => ['file' => $f, 'size' => filesize(AppRelease::dir()."/$f"), 'sha256' => hash_file('sha256', AppRelease::dir()."/$f")],
        ] + $extra, JSON_UNESCAPED_UNICODE));
    }

    public function test_release_is_picked_up_automatically_and_served(): void
    {
        $this->writeRelease(3, '1.2.0', ['force' => true, 'stores' => ['bazaar' => 'https://cafebazaar.ir/app/click.mija.app']]);
        $this->assertSame(1, AppRelease::sync());
        $r = AppRelease::latest();
        $this->assertSame('1.2.0', $r->version_name);
        $this->assertSame(['مورد اول', 'مورد دوم'], $r->changelog);
        $this->assertTrue($r->force);
        $this->assertSame('https://cafebazaar.ir/app/click.mija.app', setting('app_bazaar_url'));

        $j = $this->getJson('/api/app/version?code=1&flavor=direct')->assertOk()->json();
        $this->assertTrue($j['update_available']);
        $this->assertTrue($j['force']);
        $this->assertSame(3, $j['latest']['code']);
        $this->assertSame($r->direct_sha256, $j['apk']['sha256']);
        $j = $this->getJson('/api/app/version?code=3')->json();
        $this->assertFalse($j['update_available']);

        $res = $this->get('/app/download')->assertOk();
        $this->assertSame('application/vnd.android.package-archive', $res->headers->get('Content-Type'));
        $this->assertSame(1, $r->fresh()->downloads);

        $this->get('/app')->assertOk()->assertSee('دانلود مستقیم')->assertSee($r->direct_sha256)->assertSee('cafebazaar.ir', false);
        $this->get('/app/update')->assertOk()->assertSee('data-latest="3"', false);
        $ua = ['User-Agent' => 'Mozilla/5.0 Android MijaApp/1.0.0 (code 1; flavor store)'];
        $this->withHeaders($ua)->get('/app')->assertOk()->assertSee('شما از اپلیکیشن می‌جا استفاده می‌کنید');
        $this->withHeaders($ua)->get('/')->assertOk()->assertDontSee('دانلود اپلیکیشن');
        $this->flushHeaders()->get('/')->assertSee('دانلود اپلیکیشن');
    }

    public function test_new_release_notifies_app_users_only(): void
    {
        $old = User::create(['name' => 'قدیمی', 'phone' => '09130000011', 'password' => 'secret123', 'role' => 'guest', 'app_code' => 1]);
        $cur = User::create(['name' => 'به‌روز', 'phone' => '09130000012', 'password' => 'secret123', 'role' => 'guest', 'app_code' => 5]);
        $web = User::create(['name' => 'وب', 'phone' => '09130000013', 'password' => 'secret123', 'role' => 'guest']);
        $this->writeRelease(5, '2.0.0');
        AppRelease::sync();
        $this->assertSame(1, \App\Models\Notice::where('user_id', $old->id)->count());
        $this->assertSame(0, \App\Models\Notice::where('user_id', $cur->id)->count());
        $this->assertSame(0, \App\Models\Notice::where('user_id', $web->id)->count());
        // همگام‌سازی دوباره اعلان تکراری نمی‌فرستد
        AppRelease::sync();
        $this->assertSame(1, \App\Models\Notice::where('user_id', $old->id)->count());
    }

    public function test_app_client_version_is_tracked(): void
    {
        $u = User::create(['name' => 'کاربر', 'phone' => '09130000014', 'password' => 'secret123', 'role' => 'guest']);
        $this->actingAs($u)->withHeaders(['User-Agent' => 'Mozilla/5.0 Android MijaApp/1.0.1 (code 2; flavor direct)'])->get('/panel');
        $this->assertSame(2, $u->fresh()->app_code);
    }

    public function test_admin_has_no_app_settings_page(): void
    {
        $admin = User::create(['name' => 'مدیر', 'phone' => '09130000002', 'password' => 'secret123', 'role' => 'admin']);
        $this->actingAs($admin)->get('/admin/app')->assertNotFound();
    }
}
