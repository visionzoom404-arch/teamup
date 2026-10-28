<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('phone', 15)->unique();
            $t->string('email')->nullable()->unique();
            $t->string('password');
            $t->string('role', 10)->default('guest')->index(); // guest | host | admin
            $t->string('avatar')->nullable();
            $t->text('bio')->nullable();
            $t->string('city')->nullable();
            $t->string('national_id', 12)->nullable();
            $t->string('sheba', 30)->nullable();
            $t->string('bank_owner')->nullable();
            $t->bigInteger('wallet')->default(0);
            $t->boolean('is_active')->default(true);
            $t->boolean('host_verified')->default(false);
            $t->timestamp('phone_verified_at')->nullable();
            $t->rememberToken();
            $t->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $t) {
            $t->string('email')->primary();
            $t->string('token');
            $t->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $t) {
            $t->string('id')->primary();
            $t->foreignId('user_id')->nullable()->index();
            $t->string('ip_address', 45)->nullable();
            $t->text('user_agent')->nullable();
            $t->longText('payload');
            $t->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
