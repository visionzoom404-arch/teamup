<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Property;
use App\Models\User;
use App\Models\Visit;
use App\Support\Analytics;
use App\Support\Jalali;
use Illuminate\Http\Request;

/** آمار بازدید سایت برای مدیر (فقط داده‌ی خود سایت؛ جدا از گوگل آنالیتیکس) */
class AnalyticsController extends Controller
{
    public function index(Request $r)
    {
        $range = in_array((int) $r->query('days'), [1, 7, 14, 30, 90, 365], true) ? (int) $r->query('days') : 7;
        $from = today()->subDays($range - 1);
        $human = fn () => Visit::where('is_bot', false)->where('day', '>=', $from->toDateString());

        $views = (clone $human())->count();
        $uniq = (int) (clone $human())->distinct()->count('visitor');
        $new = (int) (clone $human())->where('is_new', true)->count();

        $series = [];
        $byDay = (clone $human())->selectRaw('day, count(*) v, count(distinct visitor) u')->groupBy('day')->get()->keyBy(fn ($x) => substr((string) $x->day, 0, 10));
        $botDay = Visit::where('is_bot', true)->where('day', '>=', $from->toDateString())->selectRaw('day, count(*) c')->groupBy('day')->pluck('c', 'day')->mapWithKeys(fn ($v, $k) => [substr((string) $k, 0, 10) => $v]);
        for ($i = $range - 1; $i >= 0; $i--) {
            $d = today()->subDays($i);
            $k = $d->toDateString();
            $series[] = ['day' => $k, 'label' => $range > 14 ? Jalali::format($d, 'n/j') : Jalali::format($d, 'j F'), 'views' => (int) ($byDay[$k]->v ?? 0), 'uniq' => (int) ($byDay[$k]->u ?? 0), 'bots' => (int) ($botDay[$k] ?? 0)];
        }
        $hours = array_fill(0, 24, 0);
        foreach ((clone $human())->selectRaw('hour, count(*) c')->groupBy('hour')->pluck('c', 'hour') as $h => $c) {
            $hours[(int) $h] = (int) $c;
        }
        $top = fn ($col, $lim = 10) => (clone $human())->whereNotNull($col)->selectRaw("$col k, count(*) v, count(distinct visitor) u")->groupBy($col)->orderByDesc('v')->limit($lim)->get();
        $entries = (clone $human())->where('source', '!=', 'internal');
        $sources = (clone $entries)->selectRaw('source k, count(*) v, count(distinct visitor) u')->groupBy('source')->orderByDesc('v')->get();
        $refs = (clone $entries)->whereNotNull('referrer_host')->selectRaw('referrer_host k, count(*) v')->groupBy('referrer_host')->orderByDesc('v')->limit(10)->get();
        $props = (clone $human())->whereNotNull('property_id')->selectRaw('property_id k, count(*) v, count(distinct visitor) u')->groupBy('property_id')->orderByDesc('v')->limit(10)->get();
        $titles = Property::whereIn('id', $props->pluck('k'))->pluck('title', 'id');
        $propViewers = (int) (clone $human())->whereNotNull('property_id')->distinct()->count('visitor');
        $regs = User::where('created_at', '>=', $from->startOfDay())->where('role', '!=', 'admin')->count();
        $books = Booking::where('created_at', '>=', $from->startOfDay())->count();
        $paid = Booking::whereIn('status', ['confirmed', 'completed'])->where('created_at', '>=', $from->startOfDay())->count();

        return view('admin.analytics', [
            'range' => $range, 'views' => $views, 'uniq' => $uniq, 'new' => $new, 'series' => $series, 'hours' => $hours,
            'today' => Visit::where('is_bot', false)->where('day', today()->toDateString())->count(),
            'todayU' => (int) Visit::where('is_bot', false)->where('day', today()->toDateString())->distinct()->count('visitor'),
            'yesterday' => Visit::where('is_bot', false)->where('day', today()->subDay()->toDateString())->count(),
            'online' => (int) Visit::where('is_bot', false)->where('created_at', '>=', now()->subMinutes(5))->distinct()->count('visitor'),
            'pages' => (clone $human())->selectRaw('path, max(label) label, count(*) v, count(distinct visitor) u')->groupBy('path')->orderByDesc('v')->limit(12)->get(),
            'sources' => $sources, 'sourceTotal' => max(1, (int) $sources->sum('v')), 'refs' => $refs,
            'devices' => $top('device'), 'browsers' => $top('browser', 8), 'oses' => $top('os', 8),
            'searches' => (clone $human())->whereNotNull('search')->selectRaw('search k, count(*) v')->groupBy('search')->orderByDesc('v')->limit(10)->get(),
            'props' => $props, 'titles' => $titles,
            'roles' => (clone $human())->selectRaw("coalesce(role,'anon') k, count(*) v")->groupBy('role')->pluck('v', 'k'),
            'bots' => Visit::where('is_bot', true)->where('day', '>=', $from->toDateString())->selectRaw('bot_name k, count(*) v, max(created_at) last')->groupBy('bot_name')->orderByDesc('v')->get(),
            'recent' => Visit::where('is_bot', false)->latest('id')->limit(30)->get(),
            'funnel' => ['visitors' => $uniq, 'viewers' => $propViewers, 'regs' => $regs, 'books' => $books, 'paid' => $paid],
            'srcNames' => Analytics::SOURCE_NAMES, 'devNames' => Analytics::DEVICE_NAMES,
            'hasData' => Visit::exists(),
        ]);
    }
}
