<?php

namespace App\Http\Middleware;

use App\Models\Property;
use App\Models\User;
use App\Models\Visit;
use App\Support\Analytics;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

/**
 * آمار بازدید برای مدیر: هر صفحه‌ی HTML موفق (GET) ثبت می‌شود.
 * IP ذخیره نمی‌شود؛ بازدیدکننده با یک کوکی تصادفی شمرده می‌شود. ربات‌های جستجو جدا ثبت می‌شوند.
 */
class TrackVisit
{
    public function handle(Request $request, Closure $next)
    {
        $ua = (string) $request->userAgent();
        if ($request->isMethod('GET') && ! Analytics::bot($ua)) {
            $vid = (string) $request->cookie('mj_v');
            $isNew = ! preg_match('/^[A-Za-z0-9]{16,24}$/', $vid);
            if ($isNew) {
                $vid = Str::random(20);
            }
            Cookie::queue(cookie('mj_v', $vid, 60 * 24 * 400, '/', null, $request->isSecure(), true, false, 'lax'));
            $request->attributes->set('mj.vid', [$vid, $isNew]);
        }

        return $next($request);
    }

    public function terminate(Request $request, $response): void
    {
        try {
            if (! $request->isMethod('GET') || $response->getStatusCode() !== 200 || $request->expectsJson() || $request->ajax()) {
                return;
            }
            if (! str_contains((string) $response->headers->get('Content-Type'), 'text/html')) {
                return;
            }
            $path = '/'.ltrim($request->path(), '/');
            if (preg_match('~^/(admin|impersonate|api|storage|gateway|pay|ad|s|js|css|img|vendor|build|up|notices/(poll|live))(/|$)~', $path) || session()->has('impersonator_id')) {
                return;
            }
            $u = $request->user();
            if ($u && $u->role === 'admin') {
                return;
            }
            $ua = (string) $request->userAgent();
            $bot = Analytics::bot($ua);
            $app = (bool) app_client();
            [$src, $refHost] = Analytics::source($request->headers->get('referer'), $request->query('utm_source') ?: $request->query('ref'), $request->getHost());
            $route = $request->route();
            $rn = $route?->getName();
            $prop = $route?->parameter('property');
            $propId = $prop instanceof Property ? $prop->id : null;
            $q = $rn === 'search' ? trim((string) ($request->query('q') ?: $request->query('city') ?: '')) : '';

            $label = match (true) {
                $path === '/' => 'صفحه‌ی اول',
                $rn === 'search' => 'جستجو'.($q !== '' ? ': '.$q : ''),
                $prop instanceof Property => 'اقامتگاه: '.$prop->title,
                $rn === 'host.public' && $route->parameter('user') instanceof User => 'پروفایل میزبان: '.$route->parameter('user')->name,
                $rn === 'page' => 'صفحه: '.$route->parameter('slug'),
                default => $path,
            };
            [$vid, $isNew] = $request->attributes->get('mj.vid', [null, false]);
            Visit::create([
                'day' => today(), 'hour' => (int) now()->format('G'), 'created_at' => now(),
                'visitor' => $bot ? null : $vid, 'is_new' => ! $bot && $isNew, 'is_bot' => (bool) $bot, 'bot_name' => $bot,
                'path' => mb_substr($path, 0, 190), 'label' => mb_substr($label, 0, 150), 'route' => $rn ? mb_substr($rn, 0, 40) : null, 'property_id' => $propId,
                'source' => $bot ? 'direct' : $src, 'referrer_host' => $refHost,
                'device' => $bot ? null : Analytics::device($ua, $app), 'browser' => $bot ? null : Analytics::browser($ua, $app), 'os' => $bot ? null : Analytics::os($ua),
                'role' => $u ? $u->role : null, 'search' => $q !== '' ? mb_substr($q, 0, 80) : null,
            ]);
            if (random_int(1, 800) === 1) {
                Visit::where('created_at', '<', now()->subDays(400))->delete();
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
