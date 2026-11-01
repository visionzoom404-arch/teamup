<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** هر روش پرداخت کلید روشن/خاموش جدا دارد؛ از تنظیم قبلی «درگاه» مهاجرت می‌کنیم */
return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::table('settings')->where('key', 'gateway_driver')->value('value');
        $set = fn ($k, $v) => DB::table('settings')->insertOrIgnore(['key' => $k, 'value' => $v]);
        $set('pay_zibal_on', $driver === 'zibal' ? 1 : 0);
        $set('pay_sandbox_on', $driver === 'sandbox' || $driver === null ? 1 : 0);   // رفتار قبلی حفظ می‌شود؛ مدیر در سایت واقعی خاموشش کند
        $set('pay_bitpay_on', 0);
        $set('pay_wallet_on', 1);
        \Illuminate\Support\Facades\Cache::forget('mija.settings');
    }

    public function down(): void
    {
    }
};
