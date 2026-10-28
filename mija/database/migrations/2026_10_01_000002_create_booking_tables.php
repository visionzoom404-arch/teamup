<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $t) {
            $t->id();
            $t->foreignId('host_id')->nullable()->constrained('users')->cascadeOnDelete();
            $t->string('code', 30)->unique();
            $t->string('type', 10)->default('percent'); // percent | fixed
            $t->unsignedBigInteger('value');
            $t->unsignedBigInteger('max_discount')->nullable();
            $t->unsignedBigInteger('min_amount')->default(0);
            $t->unsignedInteger('usage_limit')->nullable();
            $t->unsignedInteger('used_count')->default(0);
            $t->timestamp('expires_at')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('bookings', function (Blueprint $t) {
            $t->id();
            $t->string('code', 12)->unique();
            $t->foreignId('property_id')->constrained()->cascadeOnDelete();
            $t->foreignId('guest_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('host_id')->constrained('users')->cascadeOnDelete();
            $t->date('check_in');
            $t->date('check_out');
            $t->unsignedSmallInteger('nights');
            $t->unsignedSmallInteger('adults')->default(1);
            $t->unsignedSmallInteger('children')->default(0);
            $t->unsignedBigInteger('subtotal');
            $t->unsignedBigInteger('discount')->default(0);
            $t->string('coupon_code', 30)->nullable();
            $t->unsignedBigInteger('service_fee')->default(0);
            $t->unsignedBigInteger('total');
            $t->unsignedBigInteger('commission')->default(0);
            $t->unsignedBigInteger('host_payout')->default(0);
            $t->json('breakdown')->nullable();
            $t->string('status', 20)->default('pending_host')->index();
            $t->string('cancellation_policy', 12)->default('moderate');
            $t->text('guest_note')->nullable();
            $t->timestamp('respond_by')->nullable();
            $t->timestamp('pay_by')->nullable();
            $t->timestamp('paid_at')->nullable();
            $t->timestamp('settled_at')->nullable();
            $t->timestamp('cancelled_at')->nullable();
            $t->unsignedBigInteger('refund_amount')->default(0);
            $t->string('cancel_reason')->nullable();
            $t->timestamps();
            $t->index(['property_id', 'check_in', 'check_out']);
        });

        Schema::create('payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('booking_id')->nullable()->constrained()->cascadeOnDelete();
            $t->string('purpose', 12)->default('booking'); // booking | topup
            $t->unsignedBigInteger('amount');
            $t->string('gateway', 20)->default('sandbox');
            $t->string('authority', 64)->nullable()->index();
            $t->string('ref_id', 64)->nullable();
            $t->string('status', 10)->default('pending'); // pending paid failed refunded
            $t->timestamp('paid_at')->nullable();
            $t->timestamps();
        });

        Schema::create('reviews', function (Blueprint $t) {
            $t->id();
            $t->foreignId('booking_id')->unique()->constrained()->cascadeOnDelete();
            $t->foreignId('property_id')->constrained()->cascadeOnDelete();
            $t->foreignId('guest_id')->constrained('users')->cascadeOnDelete();
            $t->unsignedTinyInteger('rating');
            $t->unsignedTinyInteger('r_clean')->default(5);
            $t->unsignedTinyInteger('r_accuracy')->default(5);
            $t->unsignedTinyInteger('r_location')->default(5);
            $t->unsignedTinyInteger('r_host')->default(5);
            $t->unsignedTinyInteger('r_value')->default(5);
            $t->text('comment')->nullable();
            $t->text('host_reply')->nullable();
            $t->boolean('is_published')->default(true);
            $t->timestamps();
        });

        Schema::create('messages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $t->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $t->text('body');
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
        });

        Schema::create('wallet_transactions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $t->string('type', 8); // credit | debit
            $t->bigInteger('amount');
            $t->bigInteger('balance_after');
            $t->string('reason');
            $t->timestamps();
        });

        Schema::create('payouts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('host_id')->constrained('users')->cascadeOnDelete();
            $t->unsignedBigInteger('amount');
            $t->string('sheba', 30);
            $t->string('bank_owner')->nullable();
            $t->string('status', 10)->default('pending'); // pending paid rejected
            $t->string('note')->nullable();
            $t->string('tracking_code')->nullable();
            $t->timestamp('processed_at')->nullable();
            $t->timestamps();
        });

        Schema::create('notices', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('title');
            $t->string('body')->nullable();
            $t->string('url')->nullable();
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
        });

        Schema::create('cache', function (Blueprint $t) {
            $t->string('key')->primary();
            $t->mediumText('value');
            $t->integer('expiration')->index();
        });
        Schema::create('cache_locks', function (Blueprint $t) {
            $t->string('key')->primary();
            $t->string('owner');
            $t->integer('expiration')->index();
        });
    }

    public function down(): void
    {
        foreach (['cache_locks', 'cache', 'notices', 'payouts', 'wallet_transactions', 'messages', 'reviews', 'payments', 'bookings', 'coupons'] as $x) {
            Schema::dropIfExists($x);
        }
    }
};
