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

    public function test_admin_publishes_and_clients_see_update(): void
    {
        $admin = User::create(['name' => 'مدیر', 'phone' => '09130000001', 'password' => 'secret123', 'role' => 'admin']);
        $this->actingAs($admin)->get('/admin/app')->assertOk();
        $this->actingAs($admin)->post('/admin/app', [
            'version_name' => '1.2.0', 'version_code' => 3, 'changelog' => "مورد اول\nمورد دوم",
            'direct' => $this->fakeApk('a.apk'), 'store' => $this->fakeApk('b.apk'), 'force' => 1,
        ])->assertSessionHasNoErrors();

        $r = AppRelease::latest();
        $this->assertSame('1.2.0', $r->version_name);
        $this->assertSame(['مورد اول', 'مورد دوم'], $r->changelog);
        $this->assertTrue($r->force);
        $this->assertNotNull($r->path('direct'));
        $this->assertSame(hash_file('sha256', $r->path('direct')), $r->direct_sha256);

        // کلاینت قدیمی: به‌روزرسانی اجباری
        $j = $this->getJson('/api/app/version?code=1&flavor=direct')->assertOk()->json();
        $this->assertTrue($j['update_available']);
        $this->assertTrue($j['force']);
        $this->assertSame(3, $j['latest']['code']);
        $this->assertSame($r->direct_sha256, $j['apk']['sha256']);
        // کلاینت به‌روز
        $j = $this->getJson('/api/app/version?code=3')->json();
        $this->assertFalse($j['update_available']);
        $this->assertFalse($j['force']);

        // دانلود عمومی
        $res = $this->get('/app/download')->assertOk();
        $this->assertSame('application/vnd.android.package-archive', $res->headers->get('Content-Type'));
        $this->assertSame(1, $r->fresh()->downloads);

        // صفحات عمومی با نسخه
        $this->get('/app')->assertOk()->assertSee('دانلود مستقیم')->assertSee($r->direct_sha256);
        $this->get('/app/update')->assertOk()->assertSee('data-latest="3"', false);
        // درون‌برنامه: بدون لینک دانلود اپ در فوتر
        $ua = ['User-Agent' => 'Mozilla/5.0 Android MijaApp/1.0.0 (code 1; flavor store)'];
        $this->withHeaders($ua)->get('/app')->assertOk()->assertSee('شما از اپلیکیشن می‌جا استفاده می‌کنید');
        $this->withHeaders($ua)->get('/')->assertOk()->assertDontSee('دانلود اپلیکیشن');
        $this->flushHeaders()->get('/')->assertSee('دانلود اپلیکیشن');
    }

    public function test_admin_validation_and_toggle(): void
    {
        $admin = User::create(['name' => 'مدیر', 'phone' => '09130000002', 'password' => 'secret123', 'role' => 'admin']);
        $bad = UploadedFile::fake()->create('x.txt', 10);
        $this->actingAs($admin)->post('/admin/app', ['version_name' => 'abc', 'version_code' => 1, 'direct' => $bad])->assertSessionHasErrors(['version_name']);
        $this->actingAs($admin)->post('/admin/app', ['version_name' => '1.0.0', 'version_code' => 1, 'direct' => UploadedFile::fake()->create('x.apk', 10)])->assertStatus(422);
        $this->actingAs($admin)->post('/admin/app', ['version_name' => '1.0.0', 'version_code' => 1, 'direct' => $this->fakeApk('a.apk')]);
        $r = AppRelease::first();
        $this->actingAs($admin)->post("/admin/app/{$r->id}/toggle", ['field' => 'published']);
        $this->assertNull(AppRelease::latest());
        $this->actingAs($admin)->post('/admin/app/stores', ['app_bazaar_url' => 'https://cafebazaar.ir/app/click.mija.app', 'app_myket_url' => ''])->assertSessionHasNoErrors();
        $this->assertSame('https://cafebazaar.ir/app/click.mija.app', setting('app_bazaar_url'));
        $this->actingAs($admin)->delete("/admin/app/{$r->id}");
        $this->assertSame(0, AppRelease::count());
        // غیرمدیر دسترسی ندارد
        $guest = User::create(['name' => 'مهمان', 'phone' => '09130000003', 'password' => 'secret123', 'role' => 'guest']);
        $this->actingAs($guest)->get('/admin/app')->assertForbidden();
    }

    public function test_sync_from_release_json(): void
    {
        file_put_contents(AppRelease::dir().'/mija-direct-1.0.0.apk', "PK\x03\x04abc");
        file_put_contents(AppRelease::dir().'/release.json', json_encode(['version_name' => '1.0.0', 'version_code' => 1, 'changelog' => ['الف'], 'direct' => ['file' => 'mija-direct-1.0.0.apk', 'size' => 7, 'sha256' => 'aa']]));
        $this->assertSame(1, AppRelease::sync());
        $this->assertSame(0, AppRelease::sync());
        $this->get('/app')->assertOk()->assertSee('دانلود مستقیم');
    }
}
