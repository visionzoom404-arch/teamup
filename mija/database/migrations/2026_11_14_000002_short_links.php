<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** لینک کوتاه برای متغیر %link% در پترن‌های پیامک (محدودیت طول متغیر در ippanel) */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('short_links', function (Blueprint $t) {
            $t->id();
            $t->string('code', 12)->unique();
            $t->string('hash', 40)->unique();
            $t->text('url');
            $t->unsignedInteger('clicks')->default(0);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('short_links');
    }
};
