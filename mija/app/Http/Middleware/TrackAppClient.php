<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** ثبت نسخه‌ی اپلیکیشنِ کاربر وارد‌شده (برای ارسال اعلان به‌روزرسانی فقط به کاربران اپ) */
class TrackAppClient
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        try {
            $c = app_client();
            $u = $request->user();
            if ($c && $u && ! session()->has('impersonator_id') && (int) $u->app_code !== $c['code']) {
                $u->forceFill(['app_code' => $c['code'], 'app_seen_at' => now()])->saveQuietly();
            }
        } catch (\Throwable) {
        }

        return $response;
    }
}
