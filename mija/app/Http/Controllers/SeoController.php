<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Support\DefaultPages;

/** robots.txt و sitemap.xml پویا برای mija.click */
class SeoController extends Controller
{
    public function robots()
    {
        $body = "User-agent: *\nAllow: /\n"
            ."Disallow: /admin\nDisallow: /host\nDisallow: /panel\nDisallow: /login\nDisallow: /register\nDisallow: /bookings\nDisallow: /notices\nDisallow: /profile\nDisallow: /gateway\nDisallow: /pay\nDisallow: /ad/\nDisallow: /impersonate\n"
            ."Disallow: /search?*check_in=\nDisallow: /search?*sort=\nDisallow: /search?*page=\n\n"
            .'Sitemap: '.url('/sitemap.xml')."\n";

        return response($body, 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }

    public function sitemap()
    {
        $u = [];
        $add = function (string $loc, ?string $mod = null, string $freq = 'weekly', string $pri = '0.6', array $imgs = []) use (&$u) {
            $u[] = compact('loc', 'mod', 'freq', 'pri', 'imgs');
        };
        $add(url('/'), now()->toDateString(), 'daily', '1.0');
        $add(url('/search'), now()->toDateString(), 'daily', '0.9');
        foreach (array_keys(config('mija.regions')) as $r) {
            $add(url('/search').'?region='.$r, null, 'daily', '0.8');
        }
        foreach (array_keys(config('mija.property_types')) as $t) {
            $add(url('/search').'?'.http_build_query(['type' => [$t]]), null, 'daily', '0.8');
        }
        foreach (Property::approved()->select('city')->distinct()->pluck('city') as $c) {
            $add(url('/search').'?city='.rawurlencode($c), null, 'daily', '0.7');
        }
        foreach (array_keys(DefaultPages::SLUGS) as $slug) {
            $add(route('page', $slug), null, 'monthly', '0.4');
        }
        foreach (Property::approved()->with('images')->latest('updated_at')->get() as $p) {
            $add(route('property', $p), $p->updated_at->toDateString(), 'weekly', '0.9', $p->images->take(5)->map(fn ($i) => ['loc' => $i->url(), 'title' => $p->title])->all());
        }
        foreach (\App\Models\User::where('role', 'host')->where('is_active', true)->whereHas('properties', fn ($q) => $q->approved())->get() as $h) {
            $add(route('host.public', $h), null, 'weekly', '0.5');
        }

        return response()->view('sitemap', ['urls' => $u], 200, ['Content-Type' => 'application/xml; charset=utf-8']);
    }
}
