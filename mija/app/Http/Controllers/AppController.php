<?php

namespace App\Http\Controllers;

use App\Models\AppRelease;
use Illuminate\Http\Request;

/** صفحه‌ی دانلود/به‌روزرسانی اپلیکیشن اندروید + API نسخه */
class AppController extends Controller
{
    private function data(): array
    {
        if (! AppRelease::latest()) {
            AppRelease::sync();
        }
        $latest = AppRelease::latest();

        return [
            'latest' => $latest,
            'history' => AppRelease::where('published', true)->orderByDesc('version_code')->limit(8)->get(),
            'bazaar' => trim((string) setting('app_bazaar_url')),
            'myket' => trim((string) setting('app_myket_url')),
            'client' => app_client(),
        ];
    }

    public function index()
    {
        return view('app.index', $this->data());
    }

    public function update(Request $r)
    {
        return view('app.update', $this->data() + ['src' => $r->query('src'), 'cur' => (int) $r->query('v')]);
    }

    public function download()
    {
        $rel = AppRelease::latest();
        $path = $rel?->path('direct');
        abort_unless($path, 404, 'فایل اپلیکیشن هنوز بارگذاری نشده است.');
        $rel->increment('downloads');

        return response()->download($path, "mija-{$rel->version_name}.apk", [
            'Content-Type' => 'application/vnd.android.package-archive',
            'Cache-Control' => 'no-cache',
        ]);
    }

    /** برای خود اپ: آخرین نسخه، اجباری بودن، لینک دانلود و فروشگاه‌ها */
    public function version(Request $r)
    {
        $d = $this->data();
        $l = $d['latest'];
        if (! $l) {
            return response()->json(['ok' => false, 'message' => 'no release'])->header('Cache-Control', 'no-store');
        }
        $code = (int) $r->query('code');
        $avail = $l->version_code > $code;

        return response()->json([
            'ok' => true,
            'latest' => ['code' => $l->version_code, 'name' => $l->version_name, 'changelog' => $l->changelog ?? [], 'force' => $l->force, 'published' => $l->created_at?->toIso8601String()],
            'update_available' => $avail,
            'force' => $avail && $l->force,
            'apk' => ['url' => route('app.download'), 'size' => (int) $l->direct_size, 'sha256' => $l->direct_sha256],
            'stores' => ['bazaar' => $d['bazaar'], 'myket' => $d['myket']],
            'package' => AppRelease::PACKAGE,
        ])->header('Cache-Control', 'no-store');
    }
}
