<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $t) {
            $t->id();
            $t->string('slug', 120)->unique();
            $t->string('title', 160);
            $t->string('meta_title', 70)->nullable();
            $t->string('excerpt', 320);                    // توضیح متا و خلاصه
            $t->string('keyword', 80)->nullable();         // کلمه‌ی کلیدی اصلی
            $t->string('category', 40)->default('راهنمای رزرو');
            $t->longText('body');
            $t->string('image', 200)->nullable();          // مسیر نسبت به public یا storage
            $t->string('image_alt', 160)->nullable();
            $t->string('status', 10)->default('published')->index();   // draft|published
            $t->timestamp('published_at')->nullable()->index();
            $t->unsignedInteger('views')->default(0);
            $t->timestamps();
        });
        \App\Support\DefaultArticles::seed();
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
