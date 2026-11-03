<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms_logs', function (Blueprint $t) {
            $t->unsignedTinyInteger('attempts')->default(0);
            $t->timestamp('retry_at')->nullable()->index();
            $t->text('payload')->nullable();
            $t->string('via', 10)->nullable();   // pattern | plain
        });
        // پیام بدون کد پترن یا با پترن ناموفق، به‌صورت عادی ارسال شود
        Setting::put('sms_plain_fallback', 1);
        Setting::put('sms_use_link', 0);
    }

    public function down(): void
    {
        Schema::table('sms_logs', function (Blueprint $t) {
            $t->dropColumn(['attempts', 'retry_at', 'payload', 'via']);
        });
    }
};
