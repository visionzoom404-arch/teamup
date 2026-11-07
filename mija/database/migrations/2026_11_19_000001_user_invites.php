<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->string('invite_hash', 64)->nullable()->index();   // sha256 توکن لینک ورود شخصی (خودِ توکن ذخیره نمی‌شود)
            $t->timestamp('invite_expires_at')->nullable();
            $t->timestamp('invite_sent_at')->nullable();
            $t->timestamp('invite_used_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->dropColumn(['invite_hash', 'invite_expires_at', 'invite_sent_at', 'invite_used_at']);
        });
    }
};
