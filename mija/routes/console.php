<?php

use App\Support\BookingService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('mija:tick', function () {
    \App\Models\AppRelease::sync();   // نسخه‌ی تازه‌ی اپ اندروید (اگر فایل جدید گذاشته شده باشد) خودکار ثبت می‌شود
    $r = BookingService::tick();
    $this->info("منقضی: {$r['expired']} | تسویه: {$r['settled']} | اتمام: {$r['completed']}");
})->purpose('انقضای رزروها، تسویه با میزبان و اتمام اقامت‌ها');

Schedule::command('mija:tick')->everyMinute();
