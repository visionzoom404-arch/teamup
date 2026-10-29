<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->string('referral_code', 10)->nullable()->unique();
            $t->foreignId('referred_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('last_login_at')->nullable();
            $t->string('last_login_ip', 45)->nullable();
        });
        foreach (\Illuminate\Support\Facades\DB::table('users')->whereNull('referral_code')->pluck('id') as $id) {
            \Illuminate\Support\Facades\DB::table('users')->where('id', $id)->update(['referral_code' => strtoupper(\Illuminate\Support\Str::random(6))]);
        }

        Schema::table('bookings', function (Blueprint $t) {
            $t->timestamp('responded_at')->nullable();
            $t->unsignedBigInteger('cashback')->default(0);
        });
        Schema::table('properties', function (Blueprint $t) {
            $t->text('guide')->nullable(); // راهنمای ورود (پس از قطعی شدن رزرو)
        });
    }

    public function down(): void
    {
        Schema::table('properties', fn (Blueprint $t) => $t->dropColumn('guide'));
        Schema::table('bookings', fn (Blueprint $t) => $t->dropColumn(['responded_at', 'cashback']));
        Schema::table('users', function (Blueprint $t) {
            $t->dropConstrainedForeignId('referred_by');
            $t->dropColumn(['referral_code', 'last_login_at', 'last_login_ip']);
        });
    }
};
