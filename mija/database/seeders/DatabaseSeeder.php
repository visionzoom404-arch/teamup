<?php

namespace Database\Seeders;

use App\Models\Amenity;
use App\Models\Booking;
use App\Models\PeakPeriod;
use App\Models\Property;
use App\Models\Review;
use App\Models\Setting;
use App\Models\User;
use App\Support\BookingService;
use App\Support\Jalali;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['commission_percent', 'service_fee_percent', 'host_response_hours', 'payment_window_minutes', 'min_payout'] as $k) {
            Setting::put($k, config("mija.$k"));
        }

        foreach (config('mija.amenities') as $i => [$key, $name, $icon, $grp]) {
            Amenity::updateOrCreate(['key' => $key], ['name' => $name, 'icon' => $icon, 'grp' => $grp, 'sort' => $i]);
        }

        // ایام پیک سال آینده‌ی شمسی
        [$jy] = Jalali::parts(today());
        foreach ([$jy, $jy + 1] as $y) {
            PeakPeriod::firstOrCreate(['title' => "نوروز $y"], ['starts_on' => Jalali::carbon($y, 1, 1), 'ends_on' => Jalali::carbon($y, 1, 13)]);
        }

        if (! app()->environment('production') || env('MIJA_DEMO')) {
            $this->demo();
        }
    }

    private function demo(): void
    {
        // در حالت production رمز حساب‌های نمونه تصادفی است (فقط برای نمایش اقامتگاه‌ها)
        $pw = fn (string $d) => app()->environment('production') ? \Illuminate\Support\Str::random(24) : $d;
        if (! app()->environment('production')) {
            User::updateOrCreate(['phone' => '09120000000'], ['name' => 'مدیر می‌جا', 'password' => 'admin1234', 'role' => 'admin']);
        }
        $hosts = [
            User::updateOrCreate(['phone' => '09121111111'], ['name' => 'علی رضایی', 'bio' => 'هشت سال است در شمال میزبان مسافران هستم. تمیزی و آرامش مهمان‌ها اولویت اول من است؛ هر ساعت از شبانه‌روز پاسخگو هستم.', 'password' => $pw('host1234'), 'role' => 'host', 'host_verified' => true, 'sheba' => 'IR062960000000100324200001', 'bank_owner' => 'علی رضایی', 'city' => 'نوشهر']),
            User::updateOrCreate(['phone' => '09122222222'], ['name' => 'مریم احمدی', 'bio' => 'میزبان اقامتگاه‌های بوم‌گردی و باغ‌ویلا؛ عاشق پذیرایی با چای و شیرینی محلی.', 'password' => $pw('host1234'), 'role' => 'host', 'host_verified' => true, 'city' => 'رشت']),
        ];
        $guest = User::updateOrCreate(['phone' => '09123333333'], ['name' => 'سارا محمدی', 'password' => $pw('guest1234'), 'role' => 'guest', 'wallet' => 2000000, 'city' => 'تهران']);
        User::updateOrCreate(['phone' => '09124444444'], ['name' => 'رضا کریمی', 'password' => $pw('guest1234'), 'role' => 'guest']);

        $amen = Amenity::pluck('id', 'key');
        $items = [
            [0, 'ویلای استخردار با ویوی جنگل', 'villa', 'مازندران', 'نوشهر', 'forest', 4800000, 6500000, 9000000, 8, 12, 4, 6, 3, 320, true, 'moderate', ['pool','jacuzzi','bbq','fireplace','garden','view','wifi','parking','ac','heating','kitchen','tv','family','bedding'], true],
            [0, 'کلبه چوبی رویایی در دل جنگل', 'cottage', 'مازندران', 'کلاردشت', 'cabin', 2200000, 3000000, 4200000, 4, 6, 2, 3, 1, 70, false, 'flexible', ['fireplace','bbq','view','wifi','parking','heating','kitchen','garden','pet','bedding'], true],
            [0, 'ویلا ساحلی دو خوابه نزدیک دریا', 'villa', 'مازندران', 'بابلسر', 'sea', 3200000, 4500000, 6000000, 6, 8, 2, 3, 2, 140, true, 'moderate', ['pool','bbq','wifi','parking','ac','kitchen','tv','family','bedding','washer'], false],
            [1, 'اقامتگاه بوم‌گردی ییلاقی ماسوله', 'eco', 'گیلان', 'ماسوله', 'mountain', 1500000, 2000000, 2800000, 4, 6, 2, 3, 1, 60, false, 'flexible', ['heating','view','wifi','breakfast','host_onsite','kitchen','family'], false],
            [1, 'ویلا باغ بزرگ با استخر سرپوشیده', 'garden_villa', 'البرز', 'کرج', 'villa', 5500000, 7500000, 9500000, 10, 15, 5, 8, 4, 450, true, 'strict', ['pool','jacuzzi','sauna','bbq','garden','playground','wifi','parking','ac','heating','kitchen','tv','washer','family','bedding'], true],
            [1, 'سوئیت مدرن مشرف به کوهستان', 'suite', 'تهران', 'لواسان', 'mountain', 2800000, 3600000, 4800000, 2, 4, 1, 2, 1, 55, true, 'moderate', ['jacuzzi','view','wifi','parking','ac','heating','fireplace','kitchen','tv'], false],
            [0, 'خانه چوبی تیری‌شکل (A-Frame)', 'tiny', 'گیلان', 'املش', 'cabin', 2600000, 3400000, 4400000, 2, 4, 1, 2, 1, 45, false, 'moderate', ['fireplace','view','wifi','heating','kitchen','pet','bbq'], true],
        ];

        foreach ([['villa', 0], ['sea', 2], ['mountain', 1], ['cabin', 3]] as $k => [$kind, $v]) {
            Storage::disk('public')->put("seed/art-$k.svg", LuxArt::svg($kind, 90 + $k, $v));
        }

        $created = [];
        foreach ($items as $i => [$h, $title, $type, $prov, $city, $art, $wd, $we, $pk, $cb, $cm, $bd, $bed, $ba, $area, $inst, $pol, $am, $feat]) {
            $p = Property::firstOrNew(['title' => $title]);
            if ($p->exists) {
                $created[] = $p;
                continue;
            }
            $p->fill([
                'host_id' => $hosts[$h]->id, 'type' => $type, 'province' => $prov, 'city' => $city,
                'address' => "{$prov}، {$city}، جاده اصلی، کوچه‌ی گل‌ها، پلاک ".(10 + $i),
                'description' => "{$title}؛ اقامتگاهی دنج و تمیز برای آرامش شما و خانواده. فضای حیاط و نشیمن دلباز، آشپزخانه‌ی کامل و دسترسی آسان به جاذبه‌های اطراف.\nمناسب سفرهای خانوادگی و دوستانه؛ میزبان همیشه برای راهنمایی در دسترس است.",
                'rules' => "استعمال دخانیات در فضای بسته ممنوع است.\nسکوت پس از ساعت ۲۳ رعایت شود.\nمراسم و مهمانی با هماهنگی قبلی.",
                'capacity_base' => $cb, 'capacity_max' => $cm, 'bedrooms' => $bd, 'beds' => $bed, 'bathrooms' => $ba, 'area' => $area, 'land_area' => $area * 3,
                'price_weekday' => $wd, 'price_weekend' => $we, 'price_peak' => $pk, 'extra_guest_fee' => 250000, 'cleaning_fee' => 300000,
                'weekly_discount' => 10, 'monthly_discount' => 25, 'min_nights' => 1, 'max_nights' => 30,
                'instant_booking' => $inst, 'cancellation_policy' => $pol, 'status' => 'approved', 'featured' => $feat,
                'lat' => 36.6 + $i / 50, 'lng' => 51.5 + $i / 40,
                'guide' => "آدرس دقیق: {$prov}، {$city}، انتهای جاده‌ی اصلی.\nکد درب ورودی: ۱۲۳۴ — رمز وای‌فای: mija1234\nکلید در جاکلیدی کنار در قرار دارد. برای هر مشکلی با من تماس بگیرید.",
            ])->save();
            $p->amenities()->sync(collect($am)->map(fn ($k) => $amen[$k])->all());
            foreach (range(0, 4) as $n) {
                $path = "seed/{$p->id}-$n.svg";
                Storage::disk('public')->put($path, LuxArt::svg($art, $i * 7 + $n, $n));
                $p->images()->create(['path' => $path, 'sort' => $n]);
            }
            $created[] = $p;
        }

        // رزرو و نظر نمونه
        if (! Booking::exists()) {
            $p = $created[0];
            $past = BookingService::create($guest, $p, today()->addDays(2), today()->addDays(5), 4, 0, null, 'سفر خانوادگی');
            $past->update(['check_in' => today()->subDays(12), 'check_out' => today()->subDays(9), 'status' => 'confirmed', 'paid_at' => now()->subDays(20)]);
            BookingService::tick();
            Review::create(['booking_id' => $past->id, 'property_id' => $p->id, 'guest_id' => $guest->id, 'rating' => 5, 'comment' => 'ویلا فوق‌العاده تمیز و دنج بود، میزبان بسیار مهربان. حتماً دوباره می‌آیم.']);
            $p->update(['rating_avg' => 5, 'reviews_count' => 1]);

            $up = BookingService::create($guest, $created[2], today()->addDays(10), today()->addDays(13), 4, 1, null, null);
            $pay = \App\Models\Payment::create(['user_id' => $guest->id, 'booking_id' => $up->id, 'amount' => $up->total, 'gateway' => 'sandbox', 'authority' => \Illuminate\Support\Str::random(32)]);
            BookingService::markPaid($up, $pay, 'S100200');

            BookingService::create($guest, $created[1], today()->addDays(20), today()->addDays(22), 2, 0, null, 'سلام، ورود زودتر ممکن است؟');
        }
    }
}
