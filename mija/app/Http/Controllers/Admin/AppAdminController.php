<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppRelease;
use App\Models\Setting;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

/** مدیریت نسخه‌های اپلیکیشن اندروید */
class AppAdminController extends Controller
{
    public function index()
    {
        AppRelease::sync();

        return view('admin.app', [
            'items' => AppRelease::orderByDesc('version_code')->get(),
            'bazaar' => setting('app_bazaar_url'), 'myket' => setting('app_myket_url'),
        ]);
    }

    private function saveApk($file, string $prefix, string $ver): array
    {
        abort_unless(strtolower($file->getClientOriginalExtension()) === 'apk', 422, 'فایل باید APK باشد.');
        $head = (string) file_get_contents($file->getRealPath(), false, null, 0, 4);
        abort_unless($head === "PK\x03\x04", 422, 'فایل APK معتبر نیست.');
        File::ensureDirectoryExists(AppRelease::dir());
        $name = "mija-{$prefix}-{$ver}.apk";
        $file->move(AppRelease::dir(), $name);
        $p = AppRelease::dir().'/'.$name;

        return [$name, filesize($p), hash_file('sha256', $p)];
    }

    public function store(Request $r)
    {
        $d = $r->validate([
            'version_name' => ['required', 'regex:/^\d+(\.\d+){1,3}$/', 'max:20'],
            'version_code' => 'required|integer|min:1|unique:app_releases,version_code',
            'changelog' => 'nullable|string|max:2000',
            'direct' => 'required|file|max:81920',
            'store' => 'nullable|file|max:81920',
            'force' => 'nullable|boolean',
        ], ['version_code.unique' => 'این شماره‌ی نسخه قبلاً ثبت شده؛ باید از نسخه‌ی قبلی بزرگ‌تر باشد.']);
        $rel = new AppRelease([
            'version_name' => $d['version_name'], 'version_code' => $d['version_code'],
            'changelog' => array_values(array_filter(array_map('trim', preg_split('/\R/u', (string) ($d['changelog'] ?? ''))))),
            'force' => $r->boolean('force'), 'published' => true,
        ]);
        [$rel->direct_file, $rel->direct_size, $rel->direct_sha256] = $this->saveApk($r->file('direct'), 'direct', $d['version_name']);
        if ($r->hasFile('store')) {
            [$rel->store_file, $rel->store_size, $rel->store_sha256] = $this->saveApk($r->file('store'), 'store', $d['version_name']);
        }
        $rel->save();
        Audit::log('app.release', $rel->version_name, ['code' => $rel->version_code, 'force' => $rel->force]);

        return back()->with('ok', "نسخه‌ی {$rel->version_name} منتشر شد؛ کاربران اپ به‌زودی پیام به‌روزرسانی می‌بینند.");
    }

    public function toggle(AppRelease $release, Request $r)
    {
        $f = $r->input('field') === 'force' ? 'force' : 'published';
        $release->update([$f => ! $release->$f]);
        Audit::log('app.toggle', $release->version_name, [$f => $release->$f]);

        return back()->with('ok', 'ذخیره شد.');
    }

    public function destroy(AppRelease $release)
    {
        foreach (['direct_file', 'store_file'] as $f) {
            $release->$f && File::delete(AppRelease::dir().'/'.$release->$f);
        }
        Audit::log('app.delete', $release->version_name);
        $release->delete();

        return back()->with('ok', 'نسخه حذف شد.');
    }

    public function file(AppRelease $release, string $flavor)
    {
        $p = $release->path($flavor === 'store' ? 'store' : 'direct');
        abort_unless($p, 404);

        return response()->download($p, "mija-{$flavor}-{$release->version_name}.apk", ['Content-Type' => 'application/vnd.android.package-archive']);
    }

    public function settings(Request $r)
    {
        $d = $r->validate(['app_bazaar_url' => 'nullable|url|max:200', 'app_myket_url' => 'nullable|url|max:200']);
        foreach ($d as $k => $v) {
            Setting::put($k, $v ?? '');
        }
        Audit::log('app.stores');

        return back()->with('ok', 'لینک فروشگاه‌ها ذخیره شد.');
    }
}
