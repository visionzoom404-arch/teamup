<?php

namespace App\Http\Controllers\Host;

use App\Http\Controllers\Controller;
use App\Models\Amenity;
use App\Models\CalendarDay;
use App\Models\Property;
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
        abort_unless($p->host_id === auth()->id(), 404);

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
            'address' => 'nullable|string|max:255', 'lat' => 'nullable|numeric|between:-90,90', 'lng' => 'nullable|numeric|between:-180,180',
            'description' => 'required|string|min:30|max:4000', 'rules' => 'nullable|string|max:2000', 'guide' => 'nullable|string|max:2000',
            'capacity_base' => 'required|integer|min:1|max:50', 'capacity_max' => 'required|integer|min:1|max:60|gte:capacity_base',
            'bedrooms' => 'required|integer|min:0|max:30', 'beds' => 'required|integer|min:1|max:60', 'bathrooms' => 'required|integer|min:0|max:30',
            'area' => 'nullable|integer|min:1|max:100000', 'land_area' => 'nullable|integer|min:1|max:1000000',
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
        if ($property->images()->count() < 1) {
            return back()->withErrors(['images' => 'حداقل یک عکس برای ارسال به بررسی لازم است.']);
        }
        $property->update(['status' => 'pending', 'reject_reason' => null]);

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

        return redirect()->route('host.properties.index')->with('ok', 'اقامتگاه حذف شد.');
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
            $cells[$d] = ['date' => $key, 'dow' => $date->dayOfWeek, 'booking' => $bk, 'blocked' => (bool) ($days[$key]->is_blocked ?? false), 'price' => $days[$key]->price ?? null, 'past' => $date->lt(today())];
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
            if (! $row->is_blocked && ! $row->price) {
                $row->delete();
            }
        }

        return back()->with('ok', 'تقویم به‌روزرسانی شد.');
    }
}
