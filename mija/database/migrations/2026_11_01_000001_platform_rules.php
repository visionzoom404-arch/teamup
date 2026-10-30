<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** قوانین جدید پلتفرم: احراز هویت میزبان، قیمت‌گذاری پویا، پیشنهاد جایگزین، تبلیغات، نظرات تأییدی، اعتبار هدیه، اقساط، پیامک */
return new class extends Migration
{
    public function up(): void
    {
        // قوانین جدید زمان‌بندی: ۳۰ دقیقه برای تأیید میزبان و ۳۰ دقیقه برای پرداخت
        foreach (['host_response_minutes' => 30, 'payment_window_minutes' => 30, 'offer_minutes' => 30] as $k => $v) {
            DB::table('settings')->updateOrInsert(['key' => $k], ['value' => $v]);
        }

        Schema::table('users', function (Blueprint $t) {
            $t->string('national_code', 10)->nullable();
            $t->string('birth_date', 12)->nullable();
            $t->string('id_status', 10)->default('none'); // none pending verified rejected
            $t->string('id_note')->nullable();
            $t->string('id_photo')->nullable();
            $t->string('card_number', 19)->nullable();
            $t->string('bank_name', 40)->nullable();
            $t->timestamp('host_onboarded_at')->nullable();
            $t->unsignedBigInteger('pool_amount')->default(0);      // صندوق پنهان (فقط مدیر می‌بیند)
            $t->boolean('installment_unlocked')->default(false);
            $t->unsignedTinyInteger('commission_override')->nullable();
        });

        Schema::table('properties', function (Blueprint $t) {
            $t->json('house_rules')->nullable();
            $t->json('price_dow')->nullable();      // قیمت هر روز هفته (۰=شنبه … ۶=جمعه)
            $t->json('price_months')->nullable();   // قیمت ماهانه (۱..۱۲)
            $t->json('price_seasons')->nullable();  // قیمت فصلی (spring summer autumn winter)
            $t->string('pricing_mode', 8)->default('auto'); // auto | manual
            $t->unsignedBigInteger('ad_bump')->default(0);   // زمان آخرین نردبان (timestamp)
        });

        Schema::table('calendar_days', function (Blueprint $t) {
            $t->unsignedTinyInteger('discount_pct')->nullable(); // تخفیف فوری همان روز
        });

        Schema::table('bookings', function (Blueprint $t) {
            $t->string('offer_status', 10)->nullable(); // offered accepted declined
            $t->date('offer_check_in')->nullable();
            $t->date('offer_check_out')->nullable();
            $t->unsignedTinyInteger('offer_discount')->nullable();
            $t->timestamp('offer_expires_at')->nullable();
            $t->string('payment_method', 12)->nullable();
            $t->boolean('is_installment')->default(false);
            $t->timestamp('survey_sent_at')->nullable();
            $t->timestamp('reminded_at')->nullable();
        });

        Schema::table('payments', function (Blueprint $t) {
            $t->string('merchant', 40)->nullable();
            $t->string('track_id', 40)->nullable()->index();
            $t->string('receipt')->nullable();
            $t->string('tracking_code', 40)->nullable();
            $t->string('card_to', 19)->nullable();
            $t->string('note')->nullable();
            $t->unsignedBigInteger('installment_id')->nullable();
        });

        Schema::table('payouts', fn (Blueprint $t) => $t->string('receipt')->nullable());

        Schema::create('installments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $t->unsignedTinyInteger('seq');
            $t->unsignedBigInteger('amount');
            $t->date('due_on');
            $t->string('status', 8)->default('due'); // due paid late
            $t->timestamp('paid_at')->nullable();
            $t->timestamps();
        });

        Schema::table('reviews', function (Blueprint $t) {
            $t->string('status', 14)->default('published'); // pending_host published host_rejected removed
            $t->json('photos')->nullable();
            $t->boolean('recommend')->default(true);
            $t->string('host_reject_reason')->nullable();
            $t->string('admin_note')->nullable();
            $t->timestamp('published_at')->nullable();
        });
        DB::table('reviews')->update(['status' => DB::raw("case when is_published = 1 then 'published' else 'removed' end"), 'published_at' => DB::raw('created_at')]);

        Schema::create('guest_reviews', function (Blueprint $t) {
            $t->id();
            $t->foreignId('booking_id')->unique()->constrained()->cascadeOnDelete();
            $t->foreignId('host_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('guest_id')->constrained('users')->cascadeOnDelete();
            $t->unsignedTinyInteger('rating');
            $t->json('tags')->nullable();
            $t->text('comment')->nullable();
            $t->timestamps();
        });

        Schema::create('service_requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('category', 16)->default('other');
            $t->string('text', 400);
            $t->string('status', 10)->default('pending'); // pending accepted declined done
            $t->string('host_reply', 300)->nullable();
            $t->timestamps();
        });

        Schema::create('ads', function (Blueprint $t) {
            $t->id();
            $t->foreignId('host_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('property_id')->nullable()->constrained()->cascadeOnDelete();
            $t->string('type', 8); // banner click ladder sms push
            $t->string('title', 80)->nullable();
            $t->string('text', 200)->nullable();
            $t->string('audience', 60)->nullable(); // all | region key | city
            $t->unsignedBigInteger('budget')->default(0);
            $t->unsignedBigInteger('spent')->default(0);
            $t->unsignedInteger('units')->default(0);      // روز / تعداد پیامک / تعداد اعلان
            $t->unsignedInteger('impressions')->default(0);
            $t->unsignedInteger('clicks')->default(0);
            $t->unsignedInteger('sent')->default(0);
            $t->date('starts_on')->nullable();
            $t->date('ends_on')->nullable();
            $t->string('status', 10)->default('pending'); // pending active paused finished rejected
            $t->string('admin_note')->nullable();
            $t->timestamps();
        });

        Schema::create('cashback_lots', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $t->unsignedBigInteger('amount');
            $t->unsignedBigInteger('remaining');
            $t->timestamp('expires_at');
            $t->string('status', 8)->default('active'); // active expired used
            $t->timestamps();
        });

        Schema::create('sms_logs', function (Blueprint $t) {
            $t->id();
            $t->string('phone', 15);
            $t->text('message');
            $t->string('status', 8)->default('logged'); // sent failed logged
            $t->string('response')->nullable();
            $t->timestamps();
        });

        Schema::create('banned_words', function (Blueprint $t) {
            $t->id();
            $t->string('word', 60)->unique();
            $t->timestamps();
        });

        Schema::create('pages', function (Blueprint $t) {
            $t->id();
            $t->string('slug', 40)->unique();
            $t->string('title');
            $t->longText('body');
            $t->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('action', 60);
            $t->string('subject')->nullable();
            $t->text('meta')->nullable();
            $t->string('ip', 45)->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['activity_logs', 'pages', 'banned_words', 'sms_logs', 'cashback_lots', 'ads', 'service_requests', 'guest_reviews', 'installments'] as $x) {
            Schema::dropIfExists($x);
        }
    }
};
