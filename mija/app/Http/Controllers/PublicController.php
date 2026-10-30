<?php

namespace App\Http\Controllers;

use App\Models\Amenity;
use App\Models\Property;
use App\Models\Review;
use App\Support\Ads;
use App\Support\Jalali;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PublicController extends Controller
{
    public function home()
    {
        $base = Property::approved()->with(['images', 'host']);
        $flashIds = \App\Models\CalendarDay::whereBetween('date', [today()->toDateString(), today()->addDay()->toDateString()])->where('discount_pct', '>', 0)->pluck('property_id');

        return view('home', [
            'banners' => Ads::banners(),
            'flash' => (clone $base)->whereIn('id', $flashIds)->take(8)->get(),
            'featured' => (clone $base)->where('featured', true)->latest('id')->take(8)->get(),
            'latest' => (clone $base)->latest('id')->take(8)->get(),
            'top' => (clone $base)->where('reviews_count', '>', 0)->orderByDesc('rating_avg')->take(4)->get(),
            'discounted' => (clone $base)->where(fn ($w) => $w->where('weekly_discount', '>', 0)->orWhere('monthly_discount', '>', 0))->orderByDesc('monthly_discount')->take(8)->get(),
            'instant' => (clone $base)->where('instant_booking', true)->latest('id')->take(8)->get(),
            'typeCounts' => Property::approved()->selectRaw('type, count(*) c')->groupBy('type')->pluck('c', 'type'),
            'regionCounts' => collect(config('mija.regions'))->map(fn ($rg) => Property::approved()->whereIn('province', $rg['provinces'])->count()),
            'cities' => Property::approved()->select('city')->distinct()->pluck('city'),
            'destinations' => Property::approved()->selectRaw('city, province, count(*) as c, min(price_weekday) as p')->groupBy('city', 'province')->orderByDesc('c')->take(8)->get(),
            'stats' => [
                'properties' => Property::approved()->count(),
                'cities' => Property::approved()->distinct('city')->count('city'),
                'guests' => \App\Models\Booking::whereIn('status', ['confirmed', 'completed'])->distinct('guest_id')->count('guest_id'),
                'rating' => round((float) Review::published()->avg('rating'), 1),
            ],
            'reviews' => Review::with(['guest', 'property'])->published()->whereNotNull('comment')->where('comment', '!=', '')->latest('id')->take(9)->get(),
        ]);
    }

    /** پارس تاریخ شمسی (۱۴۰۵/۰۳/۱۲) یا میلادی ورودی */
    public static function parseDate(?string $v): ?Carbon
    {
        if (! $v) {
            return null;
        }
        $v = Jalali::latinDigits(trim($v));
        try {
            if (preg_match('#^(\d{4})[/-](\d{1,2})[/-](\d{1,2})$#', $v, $m)) {
                return (int) $m[1] < 1700 ? Jalali::carbon((int) $m[1], (int) $m[2], (int) $m[3]) : Carbon::create((int) $m[1], (int) $m[2], (int) $m[3])->startOfDay();
            }
        } catch (\Throwable) {
        }

        return null;
    }

    public function search(Request $r)
    {
        $q = Property::approved()->with(['images', 'host']);
        $in = self::parseDate($r->check_in);
        $out = self::parseDate($r->check_out);

        if ($r->filled('q')) {
            $term = '%'.$r->q.'%';
            $q->where(fn ($w) => $w->where('title', 'like', $term)->orWhere('city', 'like', $term)->orWhere('province', 'like', $term));
        }
        $r->filled('province') && $q->where('province', $r->province);
        if ($r->filled('region') && ($rg = config('mija.regions.'.$r->region))) {
            $q->whereIn('province', $rg['provinces']);
        }
        $r->boolean('discount') && $q->where(fn ($w) => $w->where('weekly_discount', '>', 0)->orWhere('monthly_discount', '>', 0));
        $r->filled('city') && $q->where('city', $r->city);
        $r->filled('type') && $q->whereIn('type', (array) $r->type);
        $r->filled('guests') && $q->where('capacity_max', '>=', (int) $r->guests);
        $r->filled('bedrooms') && $q->where('bedrooms', '>=', (int) $r->bedrooms);
        $r->filled('min_price') && $q->where('price_weekday', '>=', (int) $r->min_price);
        $r->filled('max_price') && $q->where('price_weekday', '<=', (int) $r->max_price);
        $r->boolean('instant') && $q->where('instant_booking', true);
        foreach ((array) $r->amenity as $key) {
            $q->whereHas('amenities', fn ($a) => $a->where('key', $key));
        }
        if ($in && $out && $out->gt($in)) {
            $q->available($in, $out);
        }

        match ($r->sort) {
            'cheap' => $q->orderBy('price_weekday'),
            'expensive' => $q->orderByDesc('price_weekday'),
            'rating' => $q->orderByDesc('rating_avg'),
            'new' => $q->latest('id'),
            default => $q->orderByRaw('(ad_bump > ?) desc', [time() - 86400])->orderByDesc('featured')->orderByDesc('rating_avg')->latest('id'),
        };

        $items = $q->paginate(12)->withQueryString();
        if ($in && $out && $out->gt($in)) {
            $g = max(1, (int) $r->guests);
            $items->getCollection()->each(function ($p) use ($in, $out, $g) {
                $qt = $p->quote($in, $out, min($g, $p->capacity_max));
                if ($qt['ok']) {
                    $p->quote_total = $qt['total'];
                    $p->quote_nights = $qt['nights'];
                }
            });
        }

        $sponsored = ($r->page ?? 1) == 1 ? Ads::sponsored(2) : collect();
        $sponsored->each(fn ($a) => $a->increment('impressions'));

        // سئو: صفحه‌های دسته‌بندی ساده ایندکس می‌شوند؛ ترکیب فیلترها خیر
        $only = collect($r->query())->except('page')->filter(fn ($v) => $v !== null && $v !== '');
        $label = $r->filled('city') ? $r->city : ($r->filled('region') ? (config('mija.regions.'.$r->region.'.name') ?? null) : (($r->filled('type') && count((array) $r->type) === 1) ? (config('mija.property_types.'.((array) $r->type)[0]) ?? null) : null));
        $simple = $only->count() === 1 && $label && collect(['city', 'region', 'type'])->contains(fn ($k) => $only->has($k));
        $seo = [
            'title' => $simple ? ($r->filled('city') ? "اجاره ویلا و کلبه در {$label}" : ($r->filled('region') ? "رزرو ویلا و کلبه در {$label}" : "رزرو و اجاره {$label}")) : 'جستجو و رزرو ویلا و کلبه',
            'desc' => $simple ? "رزرو آنلاین و اجاره روزانه {$label}؛ مشاهده‌ی عکس، قیمت شبانه، تقویم و نظرات مهمانان و رزرو مستقیم از میزبان در می‌جا." : 'جستجوی ویلا، کلبه و اقامتگاه در سراسر ایران؛ فیلتر براساس شهر، قیمت، ظرفیت و امکانات و رزرو آنلاین در می‌جا.',
            'canonical' => $simple ? url('/search').'?'.http_build_query($only->all()) : url('/search'),
            'robots' => ($only->isEmpty() || $simple) ? 'index,follow' : 'noindex,follow',
        ];

        return view('search', [
            'seo' => $seo,
            'sponsored' => $sponsored,
            'items' => $items,
            'amenities' => Amenity::orderBy('sort')->get(),
            'in' => $in, 'out' => $out,
        ]);
    }

    public function show(Property $property, Request $r)
    {
        $owner = auth()->id() === $property->host_id || auth()->user()?->isAdmin();
        abort_unless($property->status === 'approved' || $owner, 404);
        $property->increment('views');
        $property->load(['images', 'amenities', 'host', 'reviews.guest']);

        $similar = Property::approved()->with('images')->where('id', '!=', $property->id)->where(fn ($w) => $w->where('city', $property->city)->orWhere('type', $property->type))->take(4)->get();

        // روزهای غیرقابل‌رزرو برای تقویم (۱۸۰ روز آینده)
        $from = today();
        $to = today()->addDays(240);
        $blocked = $property->days()->where('is_blocked', true)->whereBetween('date', [$from, $to])->pluck('date')->map->toDateString()->all();
        $booked = [];
        foreach ($property->bookings()->active()->where('check_out', '>', $from)->get() as $b) {
            for ($d = $b->check_in->copy(); $d->lt($b->check_out); $d->addDay()) {
                $booked[] = $d->toDateString();
            }
        }

        // قیمت هر شب برای نمایش روی تقویم
        $overrides = $property->days()->whereBetween('date', [$from, $to])->get()->keyBy(fn ($d) => $d->date->toDateString());
        $peaks = \App\Models\PeakPeriod::where('ends_on', '>=', $from)->where('starts_on', '<=', $to)->get();
        $prices = [];
        for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
            $prices[$d->toDateString()] = \App\Support\Pricing::displayPrice($property, $d, $overrides, $peaks);
        }

        $rv = $property->reviews()->selectRaw('avg(r_clean) c, avg(r_accuracy) a, avg(r_location) l, avg(r_host) h, avg(r_value) v')->first();
        $breakdown = $property->reviews_count && $rv ? ['پاکیزگی' => $rv->c, 'مطابقت با آگهی' => $rv->a, 'موقعیت' => $rv->l, 'برخورد میزبان' => $rv->h, 'ارزش نسبت به قیمت' => $rv->v] : [];

        return view('property', [
            'p' => $property, 'similar' => $similar, 'prices' => $prices, 'breakdown' => $breakdown, 'hs' => $property->host->hostStats(),
            'unavailable' => array_values(array_unique(array_merge($blocked, $booked))),
            'approx' => $property->approxCoords(),
            'isFav' => auth()->check() && auth()->user()->favorites()->whereKey($property->id)->exists(),
        ]);
    }

    public function quote(Property $property, Request $r)
    {
        $in = self::parseDate($r->check_in);
        $out = self::parseDate($r->check_out);
        if (! $in || ! $out) {
            return response()->json(['ok' => false, 'error' => 'تاریخ ورود و خروج را انتخاب کنید.']);
        }
        $guests = max(1, (int) $r->adults + (int) $r->children);
        $q = $property->quote($in, $out, $guests, $r->coupon);
        if ($q['ok']) {
            $q['html'] = view('partials.quote', ['q' => $q, 'p' => $property])->render();
            unset($q['lines']);
        }

        return response()->json($q);
    }

    public function toggleFavorite(Property $property)
    {
        $res = auth()->user()->favorites()->toggle($property->id);

        return request()->expectsJson() ? response()->json(['fav' => ! empty($res['attached'])]) : back();
    }

    public function page(string $slug)
    {
        $page = \App\Support\DefaultPages::find($slug);
        abort_unless($page, 404);

        return $slug === 'contact' ? view('pages.contact', ['page' => $page]) : view('pages.show', ['page' => $page]);
    }
}
