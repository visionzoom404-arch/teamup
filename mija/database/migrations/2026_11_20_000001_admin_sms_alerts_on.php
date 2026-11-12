<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

/** پیامک رویدادهای نیازمند تأیید به مدیران، به‌صورت پیش‌فرض روشن شود */
return new class extends Migration
{
    public function up(): void
    {
        Setting::put('admin_sms_alerts', 1);
    }

    public function down(): void
    {
    }
};
