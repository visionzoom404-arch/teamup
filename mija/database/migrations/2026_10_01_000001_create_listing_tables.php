<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $t) {
            $t->id();
            $t->foreignId('host_id')->constrained('users')->cascadeOnDelete();
            $t->string('title');
            $t->string('slug')->unique();
            $t->string('type', 30)->index();
            $t->string('province', 60)->index();
            $t->string('city', 80)->index();
            $t->string('address')->nullable();
            $t->decimal('lat', 10, 7)->nullable();
            $t->decimal('lng', 10, 7)->nullable();
            $t->text('description')->nullable();
            $t->text('rules')->nullable();
            $t->unsignedSmallInteger('capacity_base')->default(4);
            $t->unsignedSmallInteger('capacity_max')->default(6);
            $t->unsignedSmallInteger('bedrooms')->default(1);
            $t->unsignedSmallInteger('beds')->default(1);
            $t->unsignedSmallInteger('bathrooms')->default(1);
            $t->unsignedInteger('area')->nullable();
            $t->unsignedInteger('land_area')->nullable();
            $t->unsignedBigInteger('price_weekday');
            $t->unsignedBigInteger('price_weekend');
            $t->unsignedBigInteger('price_peak')->nullable();
            $t->unsignedBigInteger('extra_guest_fee')->default(0);
            $t->unsignedBigInteger('cleaning_fee')->default(0);
            $t->unsignedTinyInteger('weekly_discount')->default(0);
            $t->unsignedTinyInteger('monthly_discount')->default(0);
            $t->unsignedSmallInteger('min_nights')->default(1);
            $t->unsignedSmallInteger('max_nights')->default(30);
            $t->string('check_in_time', 5)->default('14:00');
            $t->string('check_out_time', 5)->default('12:00');
            $t->boolean('instant_booking')->default(false);
            $t->string('cancellation_policy', 12)->default('moderate');
            $t->string('status', 12)->default('draft')->index(); // draft pending approved rejected suspended
            $t->string('reject_reason')->nullable();
            $t->boolean('featured')->default(false);
            $t->decimal('rating_avg', 3, 2)->default(0);
            $t->unsignedInteger('reviews_count')->default(0);
            $t->unsignedInteger('views')->default(0);
            $t->timestamps();
        });

        Schema::create('amenities', function (Blueprint $t) {
            $t->id();
            $t->string('key', 40)->unique();
            $t->string('name');
            $t->string('icon', 24)->default('sparkle');
            $t->string('grp', 30)->default('general');
            $t->unsignedSmallInteger('sort')->default(0);
        });

        Schema::create('amenity_property', function (Blueprint $t) {
            $t->foreignId('amenity_id')->constrained()->cascadeOnDelete();
            $t->foreignId('property_id')->constrained()->cascadeOnDelete();
            $t->primary(['amenity_id', 'property_id']);
        });

        Schema::create('property_images', function (Blueprint $t) {
            $t->id();
            $t->foreignId('property_id')->constrained()->cascadeOnDelete();
            $t->string('path');
            $t->unsignedSmallInteger('sort')->default(0);
            $t->timestamps();
        });

        Schema::create('calendar_days', function (Blueprint $t) {
            $t->id();
            $t->foreignId('property_id')->constrained()->cascadeOnDelete();
            $t->date('date');
            $t->unsignedBigInteger('price')->nullable();
            $t->boolean('is_blocked')->default(false);
            $t->unique(['property_id', 'date']);
        });

        Schema::create('favorites', function (Blueprint $t) {
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('property_id')->constrained()->cascadeOnDelete();
            $t->timestamps();
            $t->primary(['user_id', 'property_id']);
        });

        Schema::create('peak_periods', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->date('starts_on');
            $t->date('ends_on');
        });

        Schema::create('settings', function (Blueprint $t) {
            $t->string('key')->primary();
            $t->text('value')->nullable();
        });
    }

    public function down(): void
    {
        foreach (['settings', 'peak_periods', 'favorites', 'calendar_days', 'property_images', 'amenity_property', 'amenities', 'properties'] as $x) {
            Schema::dropIfExists($x);
        }
    }
};
