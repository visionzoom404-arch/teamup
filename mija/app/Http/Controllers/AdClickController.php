<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use App\Support\Ads;

class AdClickController extends Controller
{
    /** ثبت کلیک تبلیغ و هدایت به صفحه‌ی اقامتگاه */
    public function go(Ad $ad)
    {
        abort_unless($ad->status === 'active', 404);
        if (! request()->cookies->has('ad'.$ad->id)) {   // یک کلیک در ۱۰ دقیقه برای هر بازدیدکننده
            Ads::click($ad);
            cookie()->queue('ad'.$ad->id, '1', 10);
        }

        return redirect()->route('property', $ad->property);
    }
}
