<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visits', function (Blueprint $t) {
            $t->id();
            $t->date('day')->index();
            $t->unsignedTinyInteger('hour')->default(0);
            $t->timestamp('created_at')->nullable()->index();
            $t->string('visitor', 24)->nullable()->index();   // شناسه‌ی تصادفی کوکی (بدون IP)
            $t->boolean('is_new')->default(false);
            $t->boolean('is_bot')->default(false)->index();
            $t->string('bot_name', 40)->nullable();
            $t->string('path', 190);
            $t->string('label', 150)->nullable();
            $t->string('route', 40)->nullable();
            $t->unsignedBigInteger('property_id')->nullable()->index();
            $t->string('source', 24)->default('direct');       // direct|internal|google|bing|instagram|…
            $t->string('referrer_host', 90)->nullable();
            $t->string('device', 10)->nullable();               // mobile|tablet|desktop|app
            $t->string('browser', 24)->nullable();
            $t->string('os', 16)->nullable();
            $t->string('role', 10)->nullable();                 // guest|host (وارد‌شده) یا خالی (ناشناس)
            $t->string('search', 80)->nullable();
            $t->index(['day', 'is_bot']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
