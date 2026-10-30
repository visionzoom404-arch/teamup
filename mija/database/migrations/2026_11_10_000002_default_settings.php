<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** اطلاعات تماس پیش‌فرض (فقط اگر مدیر قبلاً چیزی ذخیره نکرده)، و رفع خالی ماندن کد اینماد */
return new class extends Migration
{
    public function up(): void
    {
        $defaults = [
            'contact_phones' => "پشتیبانی (موبایل) | 09331903872\nتلفن ثابت | 02634430536",
            'contact_addresses' => 'دفتر مرکزی | کرج، جهانشهر، میدان سپاه، مجتمع گلستان، طبقه اول',
        ];
        foreach ($defaults as $k => $v) {
            DB::table('settings')->insertOrIgnore(['key' => $k, 'value' => $v]);
        }
        // شماره‌ی پشتیبانی اگر خالی بود
        DB::table('settings')->where('key', 'support_phone')->where(fn ($q) => $q->whereNull('value')->orWhere('value', ''))->update(['value' => '09331903872']);
        // کد اینماد خالی‌ذخیره‌شده، پیش‌فرض را از کار می‌انداخت
        DB::table('settings')->where('key', 'enamad_html')->where(fn ($q) => $q->whereNull('value')->orWhere('value', ''))->delete();
        \Illuminate\Support\Facades\Cache::forget('mija.settings');
    }

    public function down(): void
    {
    }
};
