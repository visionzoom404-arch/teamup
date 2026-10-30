<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** تبلیغ بنری با تصویر، ورود با پیامک، رسید تسویه‌ی میزبان */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ads', function (Blueprint $t) {
            $t->string('image')->nullable();                 // تصویر بنر (آپلود میزبان یا ساخته‌ی پشتیبانی)
            $t->boolean('support_request')->default(false);  // سفارش طراحی بنر توسط پشتیبانی
            $t->string('support_text', 500)->nullable();
        });
        Schema::create('login_codes', function (Blueprint $t) {
            $t->id();
            $t->string('phone', 11)->index();
            $t->string('code_hash');
            $t->unsignedTinyInteger('attempts')->default(0);
            $t->timestamp('expires_at');
            $t->timestamps();
        });
        Schema::table('payouts', function (Blueprint $t) {
            $t->timestamp('receipt_sent_at')->nullable();
            $t->boolean('by_admin')->default(false);          // تسویه‌ی مستقیم توسط مدیر (بدون درخواست میزبان)
        });
        Schema::table('notices', function (Blueprint $t) {
            $t->string('kind', 12)->default('system')->index(); // system | admin | promo
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_codes');
        Schema::table('ads', fn (Blueprint $t) => $t->dropColumn(['image', 'support_request', 'support_text']));
        Schema::table('payouts', fn (Blueprint $t) => $t->dropColumn(['receipt_sent_at', 'by_admin']));
        Schema::table('notices', fn (Blueprint $t) => $t->dropColumn('kind'));
    }
};
