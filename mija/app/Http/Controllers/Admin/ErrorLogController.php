<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

/** نمایش خطاهای اخیر سرور برای مدیر (بدون نیاز به ورود به سرور) */
class ErrorLogController extends Controller
{
    public function index()
    {
        $files = glob(storage_path('logs/laravel*.log')) ?: [];
        usort($files, fn ($a, $b) => filemtime($b) <=> filemtime($a));
        $items = [];
        foreach (array_slice($files, 0, 2) as $f) {
            $size = filesize($f);
            $raw = (string) file_get_contents($f, false, null, max(0, $size - 400000));
            if (preg_match_all('/^\[(\d{4}-\d\d-\d\d \d\d:\d\d:\d\d)\] \w+\.(ERROR|CRITICAL|ALERT|EMERGENCY): (.+?)(?=^\[\d{4}-\d\d-\d\d |\z)/ms', $raw, $m, PREG_SET_ORDER)) {
                foreach ($m as $x) {
                    $first = trim(strtok($x[3], "\n"));
                    $items[] = ['at' => $x[1], 'msg' => mb_substr($first, 0, 400), 'at_file' => preg_match('/at ([^\s]+\.php:\d+)/', $x[3], $mm) ? str_replace(base_path().'/', '', $mm[1]) : null];
                }
            }
        }
        usort($items, fn ($a, $b) => strcmp($b['at'], $a['at']));

        return view('admin.errors', ['items' => array_slice($items, 0, 40)]);
    }
}
