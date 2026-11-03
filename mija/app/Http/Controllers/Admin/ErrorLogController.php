<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Audit;
use App\Support\ErrorExplainer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

/** گزارش خطاهای سرور برای مدیر: به فارسی ساده، با علت و دکمه‌ی رفع */
class ErrorLogController extends Controller
{
    public function index()
    {
        $files = glob(storage_path('logs/laravel*.log')) ?: [];
        usort($files, fn ($a, $b) => filemtime($b) <=> filemtime($a));
        $raws = [];
        foreach (array_slice($files, 0, 2) as $f) {
            $raws[] = (string) file_get_contents($f, false, null, max(0, filesize($f) - 400000));
        }
        $groups = [];
        foreach ($raws as $raw) {
            if (! preg_match_all('/^\[(\d{4}-\d\d-\d\d \d\d:\d\d:\d\d)\] \w+\.(ERROR|CRITICAL|ALERT|EMERGENCY): (.+?)(?=^\[\d{4}-\d\d-\d\d |\z)/ms', $raw, $m, PREG_SET_ORDER)) {
                continue;
            }
            foreach ($m as $x) {
                $first = trim(strtok($x[3], "\n"));
                $key = md5(preg_replace(['/\d+/', '/\s+/'], ['#', ' '], mb_substr($first, 0, 200)));
                $file = preg_match('/at ([^\s]+\.php:\d+)/', $x[3], $mm) ? str_replace(base_path().'/', '', $mm[1]) : null;
                $g = $groups[$key] ?? ['first' => $x[1], 'last' => $x[1], 'count' => 0, 'msg' => mb_substr($first, 0, 500), 'file' => $file, 'ex' => ErrorExplainer::explain($first.' '.mb_substr($x[3], 0, 600))];
                $g['count']++;
                $g['first'] = min($g['first'], $x[1]);
                $g['last'] = max($g['last'], $x[1]);
                $groups[$key] = $g;
            }
        }
        usort($groups, fn ($a, $b) => strcmp($b['last'], $a['last']));

        return view('admin.errors', ['groups' => array_slice($groups, 0, 40), 'levels' => ErrorExplainer::LEVELS, 'fixes' => ErrorExplainer::FIXES]);
    }

    /** رفع خودکارِ بی‌خطر (فقط فهرست مجاز) */
    public function fix(Request $r)
    {
        $a = (string) $r->input('action');
        abort_unless(isset(ErrorExplainer::FIXES[$a]), 404);
        try {
            $code = Artisan::call(ErrorExplainer::FIXES[$a][1], str_contains(ErrorExplainer::FIXES[$a][1], 'migrate') ? ['--force' => true] : []);
        } catch (\Throwable $e) {
            return back()->withErrors(['fix' => 'اجرا نشد: '.mb_substr($e->getMessage(), 0, 160)]);
        }
        Audit::log('errors.fix', $a);

        return back()->with('ok', ErrorExplainer::FIXES[$a][0].' انجام شد'.($code ? ' (با هشدار)' : '').'. اگر خطا ادامه داشت، دوباره صفحه را بررسی کنید.');
    }

    /** پاک‌کردن فایل‌های گزارش (بعد از رفع مشکل، برای شروع تازه) */
    public function clear()
    {
        foreach (glob(storage_path('logs/laravel*.log')) ?: [] as $f) {
            @file_put_contents($f, '');
        }
        Audit::log('errors.clear');

        return back()->with('ok', 'گزارش خطاها پاک شد.');
    }
}
