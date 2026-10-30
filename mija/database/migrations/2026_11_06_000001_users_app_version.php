<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** نسخه‌ی اپلیکیشن اندرویدی که کاربر استفاده می‌کند (برای اعلان به‌روزرسانی) */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->unsignedInteger('app_code')->nullable();
            $t->timestamp('app_seen_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['app_code', 'app_seen_at']));
    }
};
