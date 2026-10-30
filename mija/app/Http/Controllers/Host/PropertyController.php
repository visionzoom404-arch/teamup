<?php

namespace App\Http\Controllers\Host;

use App\Http\Controllers\Controller;
use App\Models\Amenity;
use App\Models\CalendarDay;
use App\Models\Property;
use App\Rules\Clean;
use App\Models\PropertyImage;
use App\Support\Jalali;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PropertyController extends Controller
{
    private function own(Property $p): Property
    {
        abort_unless($p->host_id === auth()->id() || auth()->user()->isAdmin(), 404);

        return $p;
    }

    public function index()
    {
        return view('host.properties', ['items' => auth()->user()->properties()->with('images')->withCount('bookings')->latest('id')->get()]);
    }

    public function create()
    {
        return view('host.property-form', ['p' => new Property(['capacity_base' => 4, 'capacity_max' => 6, 'bedrooms' => 2, 'beds' => 3, 'bathrooms' => 1, 'min_nights' => 1, 'max_nights' => 30, 'cancellation_policy' => 'moderate']), 'amenities' => Amenity::orderBy('sort')->get()]);
    }

    private function rules(): array
    {
        return [
            'title' => 'required|string|min:5|max:100',
            'type' => ['required', Rule::in(array_keys(config('mija.property_types')))],
            'province' => 'required|string|max:60', 'city' => 'required|string|max:80',
            'address' => 'required|string|min:8|max:255', 'lat' => 'required|numeric|between:-90,90', 'lng' => 'required|numeric|between:-180,180',
            'description' => ['required', 'string', 'min:30', 'max:4000', new Clean], 'rules' => ['nullable', 'string', 'max:2000', new Clean], 'guide' => 'nullable|string|max:2000',
            'house_rules' => 'nullable|array', 'house_rules.*' => 'in:0,1',
            'capacity_base' => 'required|integer|min:1|max:50', 'capacity_max' => 'required|integer|min:1|max:60|gte:capacity_base',
            'bedrooms' => 'required|integer|min:0|max:30', 'beds' => 'required|integer|min:1|max:60', 'bathrooms' => 'required|integer|min:0|max:30',
            'price_weekday' => 'required|integer|min:50000', 'price_weekend' => 'required|integer|min:50000', 'price_peak' => 'nullable|integer|min:50000',
            'extra_guest_fee' => 'nullable|integer|min:0', 'cleaning_fee' => 'nullable|integer|min:0',
            'weekly_discount' => 'nullable|integer|between:0,60', 'monthly_discount' => 'nullable|integer|between:0,70',
            'min_nights' => 'required|integer|min:1|max:30', 'max_nights' => 'required|integer|min:1|max:365|gte:min_nights',
            'check_in_time' => ['required', 'regex:/^\d{2}:\d{2}$/'], 'check_out_time' => ['required', 'regex:/^\d{2}:\d{2}$/'],
            'instant_booking' => 'nullable|boolean',
            'cancellation_policy' => ['required', Rule::in(array_keys(config('mija.cancellation_policies')))],
            'amenities' => 'nullable|array', 'amenities.*' => 'integer|exists:amenities,id',
            'images' => 'nullable|array|max:20', 'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:6144',
        ];
    }

    private function payload(Request $r): array
    {
        $d = $r->validate($this->rules());
        $d['instant_booking'] = $r->boolean('instant_booking');
        // قوانین اقامت: هر مورد مجاز (۱) یا ممنوع (۰)
        $d['house_rules'] = collect(config('mija.house_rules'))->mapWithKeys(fn ($h) => [$h[0] => (bool) ($d['house_rules'][$h[0]] ?? 0)])->all();
        foreach (['extra_guest_fee', 'cleaning_fee', 'weekly_discount', 'monthly_discount'] as $k) {
            $d[$k] = (int) ($d[$k] ?? 0);
        }

        return $d;
    }

    public function store(Request $r)
    {
        $d = $this->payload($r);
        $p = auth()->user()->properties()->create(collect($d)->except(['amenities', 'images'])->all() + ['status' => 'draft']);
        $this->sync($p, $r, $d);

        return redirect()->route('host.properties.edit', $p)->with('ok', 'اقامتگاه ذخیره شد. پس از افزودن عکس‌ها، آن را برای بررسی ارسال کنید.');
    }

    public function edit(Property $property)
    {
        return view('host.property-form', ['p' => $this->own($property)->load(['images', 'amenities']), 'amenities' => Amenity::orderBy('sort')->get()]);
    }

    public function update(Request $r, Property $property)
    {
        $this->own($property);
        $d = $this->payload($r);
        $property->update(collect($d)->except(['amenities', 'images'])->all());
        // ویرایش اقامتگاه تأییدشده، آن را دوباره به بررسی می‌فرستد
        if ($property->status === 'approved' && $r->boolean('resubmit')) {
            $property->update(['status' => 'pending']);
            \App\Models\User::notifyAdmins('اقامتگاه جدید منتظر تأیید است', $property->title, route('admin.properties.show', $property));
        }
        $this->sync($property, $r, $d);

        return back()->with('ok', 'تغییرات ذخیره شد.');
    }

    private function sync(Property $p, Request $r, array $d): void
    {
        $p->amenities()->sync($d['amenities'] ?? []);
        $sort = (int) $p->images()->max('sort');
        foreach ($r->file('images', []) as $file) {
            $p->images()->create(['path' => $file->store('properties/'.$p->id, 'public'), 'sort' => ++$sort]);
        }
    }

    public function submit(Property $property)
    {
        $this->own($property);
        abort_unless(in_array($property->status, ['draft', 'rejected']), 422);
        if ($property->images()->count() < 3) {
            return back()->withErrors(['images' => 'برای ارسال به بررسی، حداقل ۳ عکس واقعی از اقامتگاه خودتان بارگذاری کنید.']);
        }
        $property->update(['status' => 'pending', 'reject_reason' => null]);
        \App\Models\User::notifyAdmins('اقامتگاه ارسال‌شده منتظر تأیید است', $property->title, route('admin.properties.show', $property));

        return back()->with('ok', 'اقامتگاه برای بررسی تیم می‌جا ارسال شد.');
    }

    public function destroy(Property $property)
    {
        $this->own($property);
        if ($property->bookings()->whereIn('status', ['pending_host', 'awaiting_payment', 'confirmed'])->exists()) {
            return back()->withErrors(['delete' => 'این اقامتگاه رزرو فعال دارد و قابل حذف نیست.']);
        }
        Storage::disk('public')->deleteDirectory('properties/'.$property->id);
        $property->delete();

        return redirect()->route(auth()->user()->isAdmin() ? 'admin.properties' : 'host.properties.index')->with('ok', 'اقامتگاه حذف شد.');
    }

    public function destroyImage(Property $property, PropertyImage $image)
    {
        $this->own($property);
        abort_unless($image->property_id === $property->id, 404);
        Storage::disk('public')->delete($image->path);
        $image->delete();

        return back();
    }

    public function coverImage(Property $property, PropertyImage $image)
    {
        $this->own($property);
        abort_unless($image->property_id === $property->id, 404);
        $property->images()->increment('sort');
        $image->update(['sort' => 0]);

        return back()->with('ok', 'عکس شاخص تغییر کرد.');
    }

    /* ---------- تقویم ---------- */

    public function calendar(Property $property, Request $r)
    {
        $this->own($property);
        [$jy, $jm] = $r->filled('ym') ? array_map('intval', explode('-', $r->ym)) : array_slice(Jalali::parts(today()), 0, 2);
        $len = Jalali::monthLength($jy, $jm);
        $first = Jalali::carbon($jy, $jm, 1);
        $last = Jalali::carbon($jy, $jm, $len);

        $days = $property->days()->whereBetween('date', [$first->toDateString(), $last->toDateString()])->get()->keyBy(fn ($d) => $d->date->toDateString());
        $bookings = $property->bookings()->active()->with('guest')->where('check_in', '<=', $last->toDateString())->where('check_out', '>', $first->toDateString())->get();

        $cells = [];
        for ($d = 1; $d <= $len; $d++) {
            $date = Jalali::carbon($jy, $jm, $d);
            $key = $date->toDateString();
            $bk = $bookings->first(fn ($b) => $date->gte($b->check_in) && $date->lt($b->check_out));
            $cells[$d] = ['date' => $key, 'dow' => $date->dayOfWeek, 'booking' => $bk, 'blocked' => (bool) ($days[$key]->is_blocked ?? false), 'price' => $days[$key]->price ?? null, 'discount' => $days[$key]->discount_pct ?? null, 'eff' => \App\Support\Pricing::nightPrice($property, $date, $days, \App\Models\PeakPeriod::where('ends_on', '>=', $first->toDateString())->where('starts_on', '<=', $last->toDateString())->get()), 'past' => $date->lt(today())];
        }
        $prev = $jm == 1 ? [$jy - 1, 12] : [$jy, $jm - 1];
        $next = $jm == 12 ? [$jy + 1, 1] : [$jy, $jm + 1];

        return view('host.calendar', ['p' => $property, 'jy' => $jy, 'jm' => $jm, 'cells' => $cells, 'prev' => implode('-', $prev), 'next' => implode('-', $next), 'offset' => (Jalali::carbon($jy, $jm, 1)->dayOfWeek + 1) % 7]);
    }

    /** مسدود/باز کردن چند روز و تعیین قیمت دلخواه */
    public function calendarUpdate(Request $r, Property $property)
    {
        $this->own($property);
        $d = $r->validate([
            'dates' => 'required|array|min:1|max:120', 'dates.*' => 'date_format:Y-m-d',
            'action' => 'required|in:block,open,price,reset', 'price' => 'nullable|integer|min:50000',
        ]);
        foreach ($d['dates'] as $date) {
            if (Carbon::parse($date)->lt(today())) {
                continue;
            }
            $has = $property->bookings()->active()->where('check_in', '<=', $date)->where('check_out', '>', $date)->exists();
            if ($has && in_array($d['action'], ['block'])) {
                continue;
            }
            $row = CalendarDay::firstOrNew(['property_id' => $property->id, 'date' => $date]);
            match ($d['action']) {
                'block' => $row->is_blocked = true,
                'open' => $row->is_blocked = false,
                'price' => $row->price = $d['price'] ?? null,
                'reset' => [$row->is_blocked, $row->price] = [false, null],
            };
            $row->save();
            if (! $row->is_blocked && ! $row->price && ! $row->discount_pct) {
                $row->delete();
            }
        }

        return back()->with('ok', 'تقویم به‌روزرسانی شد.');
    }

    /* ---------- سیاست نرخ‌گذاری ---------- */

    public function pricing(Property $property)
    {
        $this->own($property);
        $today = today();

        return view('host.pricing', ['p' => $property, 'flash' => $property->days()->where('discount_pct', '>', 0)->where('date', '>=', $today->toDateString())->orderBy('date')->get()]);
    }

    public function pricingUpdate(Request $r, Property $property)
    {
        $this->own($property);
        $d = $r->validate([
            'pricing_mode' => 'required|in:auto,manual',
            'price_dow' => 'nullable|array', 'price_dow.*' => 'nullable|integer|min:50000',
            'price_months' => 'nullable|array', 'price_months.*' => 'nullable|integer|min:50000',
            'price_seasons' => 'nullable|array', 'price_seasons.*' => 'nullable|integer|min:50000',
            'price_peak' => 'nullable|integer|min:50000',
            'price_weekday' => 'required|integer|min:50000', 'price_weekend' => 'required|integer|min:50000',
            'extra_guest_fee' => 'nullable|integer|min:0', 'capacity_base' => 'required|integer|min:1|max:50',
        ]);
        $clean = fn ($a) => collect($a ?? [])->filter(fn ($v) => $v !== null && $v !== '')->map(fn ($v) => (int) $v)->all();
        $property->update([
            'pricing_mode' => $d['pricing_mode'], 'price_dow' => $clean($d['price_dow'] ?? []), 'price_months' => $clean($d['price_months'] ?? []),
            'price_seasons' => $clean($d['price_seasons'] ?? []), 'price_peak' => $d['price_peak'] ?? null,
            'price_weekday' => $d['price_weekday'], 'price_weekend' => $d['price_weekend'],
            'extra_guest_fee' => (int) ($d['extra_guest_fee'] ?? 0), 'capacity_base' => min($d['capacity_base'], $property->capacity_max),
        ]);

        return back()->with('ok', 'سیاست نرخ‌گذاری ذخیره شد.');
    }

    /** تخفیف فوری (لحظه‌آخری) برای امروز و فردا یا برداشتن آن */
    public function flash(Request $r, Property $property)
    {
        $this->own($property);
        $d = $r->validate(['days' => 'required|in:today,tomorrow,both,clear', 'pct' => 'nullable|integer|between:5,70']);
        $dates = match ($d['days']) { 'today' => [today()], 'tomorrow' => [today()->addDay()], default => [today(), today()->addDay()] };
        if ($d['days'] === 'clear') {
            foreach ($property->days()->where('discount_pct', '>', 0)->get() as $row) {
                $row->update(['discount_pct' => null]);
                ! $row->is_blocked && ! $row->price && $row->delete();
            }

            return back()->with('ok', 'تخفیف‌های فوری برداشته شد.');
        }
        abort_unless($d['pct'] ?? null, 422);
        $n = 0;
        foreach ($dates as $date) {
            if ($property->bookings()->active()->where('check_in', '<=', $date->toDateString())->where('check_out', '>', $date->toDateString())->exists()) {
                continue;
            }
            CalendarDay::updateOrCreate(['property_id' => $property->id, 'date' => $date->toDateString()], ['discount_pct' => $d['pct'], 'is_blocked' => false]);
            $n++;
        }

        return back()->with('ok', $n ? 'تخفیف فوری فعال شد و در بخش «تخفیف لحظه‌آخری» صفحه‌ی اول نمایش داده می‌شود.' : 'این روزها رزرو شده‌اند.');
    }

    public static function icalToken(Property $p): string
    {
        return substr(hash_hmac('sha256', 'ical'.$p->id, config('app.key')), 0, 24);
    }

    /** خروجی تقویم (iCal) برای همگام‌سازی با گوگل‌کالندر و سایر سایت‌ها */
    public function ical(Property $property, string $token)
    {
        abort_unless(hash_equals(self::icalToken($property), $token), 404);
        $ev = '';
        foreach ($property->bookings()->active()->where('check_out', '>=', today())->get() as $b) {
            $ev .= "BEGIN:VEVENT\r\nUID:{$b->code}@mija\r\nDTSTART;VALUE=DATE:{$b->check_in->format('Ymd')}\r\nDTEND;VALUE=DATE:{$b->check_out->format('Ymd')}\r\nSUMMARY:Reserved\r\nEND:VEVENT\r\n";
        }
        foreach ($property->days()->where('is_blocked', true)->where('date', '>=', today()->toDateString())->get() as $d) {
            $ev .= "BEGIN:VEVENT\r\nUID:blk{$d->id}@mija\r\nDTSTART;VALUE=DATE:{$d->date->format('Ymd')}\r\nDTEND;VALUE=DATE:{$d->date->copy()->addDay()->format('Ymd')}\r\nSUMMARY:Blocked\r\nEND:VEVENT\r\n";
        }

        return response("BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Mija//FA\r\n{$ev}END:VCALENDAR\r\n", 200, ['Content-Type' => 'text/calendar; charset=utf-8']);
    }
}
