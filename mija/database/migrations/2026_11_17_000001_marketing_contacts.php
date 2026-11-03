<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_contacts', function (Blueprint $t) {
            $t->id();
            $t->string('phone', 11)->unique();
            $t->string('name', 60)->nullable();
            $t->string('note', 120)->nullable();
            $t->string('status', 12)->default('new')->index();   // new sent registered optout failed
            $t->unsignedSmallInteger('sent_count')->default(0);
            $t->timestamp('last_sent_at')->nullable();
            $t->string('last_error', 200)->nullable();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->timestamp('registered_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_contacts');
    }
};
