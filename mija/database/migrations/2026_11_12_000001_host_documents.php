<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** مدارک میزبان: کارت ملی، عکس همراه کارت، مدرک مالکیت/اجاره */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->string('selfie_photo')->nullable();
            $t->string('ownership_doc')->nullable();
            $t->timestamp('docs_submitted_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['selfie_photo', 'ownership_doc', 'docs_submitted_at']));
    }
};
