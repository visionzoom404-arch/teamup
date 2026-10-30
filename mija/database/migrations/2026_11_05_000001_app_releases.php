<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** نسخه‌های اپلیکیشن اندروید (دانلود مستقیم + نسخه‌ی بازار/مایکت) */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_releases', function (Blueprint $t) {
            $t->id();
            $t->string('version_name', 20);
            $t->unsignedInteger('version_code')->unique();
            $t->json('changelog')->nullable();
            $t->boolean('force')->default(false);          // اجبار: نسخه‌های قدیمی‌تر باید حتماً به‌روز شوند
            $t->boolean('published')->default(true);
            $t->string('direct_file')->nullable();         // نام فایل در storage/app/apk
            $t->unsignedBigInteger('direct_size')->default(0);
            $t->string('direct_sha256', 64)->nullable();
            $t->string('store_file')->nullable();          // فایل مخصوص بازار/مایکت (برای مدیر؛ آپلود در فروشگاه)
            $t->unsignedBigInteger('store_size')->default(0);
            $t->string('store_sha256', 64)->nullable();
            $t->unsignedInteger('downloads')->default(0);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_releases');
    }
};
