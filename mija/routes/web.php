<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\GatewayController;
use App\Http\Controllers\Guest;
use App\Http\Controllers\Host;
use App\Http\Controllers\AdClickController;
use App\Http\Controllers\NoticeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicController;
use Illuminate\Support\Facades\Route;

/* ---------- عمومی ---------- */
Route::get('/robots.txt', [\App\Http\Controllers\SeoController::class, 'robots']);
Route::get('/sitemap.xml', [\App\Http\Controllers\SeoController::class, 'sitemap']);
Route::get('/', [PublicController::class, 'home'])->name('home');
Route::get('/search', [PublicController::class, 'search'])->name('search');
Route::get('/p/{property}', [PublicController::class, 'show'])->name('property');
Route::get('/p/{property}/quote', [PublicController::class, 'quote'])->name('property.quote');
Route::get('/page/{slug}', [PublicController::class, 'page'])->name('page');
Route::get('/hosts/{user}', [ProfileController::class, 'publicHost'])->name('host.public');
Route::get('/offline', fn () => view('offline'))->name('offline');
Route::get('/help', fn () => view('help'))->name('help');
Route::get('/u/{user}', [ProfileController::class, 'publicGuest'])->name('guest.public');
Route::get('/ad/{ad}/go', [AdClickController::class, 'go'])->name('ad.go');
Route::get('/ical/{property}/{token}', [Host\PropertyController::class, 'ical'])->name('ical');
Route::get('/pay/callback', [GatewayController::class, 'callback'])->name('gateway.callback');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
});
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

/* ---------- مشترک بین همه‌ی کاربران وارد‌شده ---------- */
Route::middleware('auth')->group(function () {
    Route::post('/favorite/{property}', [PublicController::class, 'toggleFavorite'])->name('favorite');
    Route::get('/notices', [NoticeController::class, 'index'])->name('notices');
    Route::post('/bookings/{booking}/message', [NoticeController::class, 'message'])->name('booking.message');
    Route::get('/bookings/{booking}/ics', [NoticeController::class, 'ics'])->name('booking.ics');
    Route::get('/bookings/{booking}/invoice', [NoticeController::class, 'invoice'])->name('booking.invoice');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/password', [ProfileController::class, 'password'])->name('profile.password');
    Route::get('/impersonate/leave', [Admin\UserController::class, 'leave'])->name('impersonate.leave');
    Route::get('/gateway/{authority}', [GatewayController::class, 'show'])->name('gateway.show');
    Route::post('/gateway/{authority}', [GatewayController::class, 'result'])->name('gateway.result');
});

/* ---------- پنل مهمان ---------- */
Route::middleware(['auth', 'role:guest'])->prefix('panel')->name('guest.')->group(function () {
    Route::get('/', [Guest\PanelController::class, 'dashboard'])->name('dashboard');
    Route::get('/bookings', [Guest\PanelController::class, 'bookings'])->name('bookings');
    Route::get('/bookings/{booking}', [Guest\BookingController::class, 'show'])->name('bookings.show');
    Route::post('/p/{property}/book', [Guest\BookingController::class, 'store'])->name('book');
    Route::post('/bookings/{booking}/pay', [Guest\BookingController::class, 'pay'])->name('bookings.pay');
    Route::post('/bookings/{booking}/cancel', [Guest\BookingController::class, 'cancel'])->name('bookings.cancel');
    Route::post('/bookings/{booking}/review', [Guest\BookingController::class, 'review'])->name('bookings.review');
    Route::post('/bookings/{booking}/offer', [Guest\BookingController::class, 'offer'])->name('bookings.offer');
    Route::post('/bookings/{booking}/request', [Guest\BookingController::class, 'request'])->name('bookings.request');
    Route::post('/installments/{installment}/pay', [Guest\BookingController::class, 'payInstallment'])->name('installments.pay');
    Route::get('/favorites', [Guest\PanelController::class, 'favorites'])->name('favorites');
    Route::get('/wallet', [Guest\PanelController::class, 'wallet'])->name('wallet');
    Route::post('/wallet/topup', [Guest\PanelController::class, 'topup'])->name('wallet.topup');
});

/* ---------- پنل میزبان ---------- */
Route::middleware(['auth', 'role:host'])->prefix('host')->name('host.')->group(function () {
    Route::get('/', [Host\PanelController::class, 'dashboard'])->name('dashboard');
    Route::get('/properties', [Host\PropertyController::class, 'index'])->name('properties.index');
    Route::get('/onboard', [Host\OnboardController::class, 'show'])->name('onboard');
    Route::post('/onboard/identity', [Host\OnboardController::class, 'identity'])->name('onboard.identity');
    Route::post('/onboard/bank', [Host\OnboardController::class, 'bank'])->name('onboard.bank');
    Route::middleware('onboarded')->group(function () {
        Route::get('/properties/create', [Host\PropertyController::class, 'create'])->name('properties.create');
        Route::post('/properties', [Host\PropertyController::class, 'store'])->name('properties.store');
    });
    Route::get('/properties/{property}/edit', [Host\PropertyController::class, 'edit'])->name('properties.edit');
    Route::post('/properties/{property}', [Host\PropertyController::class, 'update'])->name('properties.update');
    Route::post('/properties/{property}/submit', [Host\PropertyController::class, 'submit'])->name('properties.submit')->middleware('onboarded');
    Route::get('/properties/{property}/pricing', [Host\PropertyController::class, 'pricing'])->name('pricing');
    Route::post('/properties/{property}/pricing', [Host\PropertyController::class, 'pricingUpdate'])->name('pricing.update');
    Route::post('/properties/{property}/flash', [Host\PropertyController::class, 'flash'])->name('flash');
    Route::delete('/properties/{property}', [Host\PropertyController::class, 'destroy'])->name('properties.destroy');
    Route::delete('/properties/{property}/images/{image}', [Host\PropertyController::class, 'destroyImage'])->name('properties.images.destroy');
    Route::post('/properties/{property}/images/{image}/cover', [Host\PropertyController::class, 'coverImage'])->name('properties.images.cover');
    Route::get('/properties/{property}/calendar', [Host\PropertyController::class, 'calendar'])->name('calendar');
    Route::post('/properties/{property}/calendar', [Host\PropertyController::class, 'calendarUpdate'])->name('calendar.update');

    Route::get('/bookings', [Host\PanelController::class, 'bookings'])->name('bookings');
    Route::get('/bookings/{booking}', [Host\PanelController::class, 'booking'])->name('bookings.show');
    Route::post('/bookings/{booking}/accept', [Host\PanelController::class, 'accept'])->name('bookings.accept');
    Route::post('/bookings/{booking}/reject', [Host\PanelController::class, 'reject'])->name('bookings.reject');
    Route::post('/bookings/{booking}/cancel', [Host\PanelController::class, 'cancel'])->name('bookings.cancel');
    Route::post('/bookings/{booking}/rate', [Host\PanelController::class, 'rateGuest'])->name('bookings.rate');
    Route::post('/requests/{req}/reply', [Host\PanelController::class, 'serviceReply'])->name('requests.reply');
    Route::get('/guests/{user}', [Host\PanelController::class, 'guest'])->name('guest');
    Route::post('/reviews/{review}/approve', [Host\PanelController::class, 'reviewApprove'])->name('reviews.approve');
    Route::post('/reviews/{review}/reject', [Host\PanelController::class, 'reviewReject'])->name('reviews.reject');
    Route::get('/ads', [Host\AdController::class, 'index'])->name('ads');
    Route::post('/ads', [Host\AdController::class, 'store'])->name('ads.store');
    Route::post('/ads/{ad}/stop', [Host\AdController::class, 'stop'])->name('ads.stop');

    Route::get('/reviews', [Host\PanelController::class, 'reviews'])->name('reviews');
    Route::post('/reviews/{review}/reply', [Host\PanelController::class, 'reply'])->name('reviews.reply');
    Route::get('/wallet', [Host\PanelController::class, 'wallet'])->name('wallet');
    Route::post('/wallet/payout', [Host\PanelController::class, 'payout'])->name('wallet.payout');
    Route::get('/coupons', [Host\PanelController::class, 'coupons'])->name('coupons');
    Route::post('/coupons', [Host\PanelController::class, 'couponStore'])->name('coupons.store');
    Route::delete('/coupons/{coupon}', [Host\PanelController::class, 'couponDestroy'])->name('coupons.destroy');
});

/* ---------- پنل مدیریت ---------- */
// اپلیکیشن اندروید
Route::get('/app', [App\Http\Controllers\AppController::class, 'index'])->name('app');
Route::get('/app/update', [App\Http\Controllers\AppController::class, 'update'])->name('app.update');
Route::get('/app/download', [App\Http\Controllers\AppController::class, 'download'])->name('app.download')->middleware('throttle:30,1');
Route::get('/api/app/version', [App\Http\Controllers\AppController::class, 'version'])->name('app.version')->middleware('throttle:120,1');

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');
    Route::post('/tick', [Admin\DashboardController::class, 'tick'])->name('tick');
    Route::get('/search', [Admin\ContentController::class, 'search'])->name('search');

    // اقامتگاه‌ها: مشاهده، ویرایش کامل و حذف
    Route::get('/properties', [Admin\ManageController::class, 'properties'])->name('properties');
    Route::get('/properties/{property}', [Admin\ManageController::class, 'property'])->name('properties.show');
    Route::post('/properties/{property}/status', [Admin\ManageController::class, 'propertyStatus'])->name('properties.status');
    Route::post('/properties/{property}/feature', [Admin\ManageController::class, 'propertyFeature'])->name('properties.feature');
    Route::get('/properties/{property}/edit', [Host\PropertyController::class, 'edit'])->name('properties.edit');
    Route::post('/properties/{property}/update', [Host\PropertyController::class, 'update'])->name('properties.update');
    Route::delete('/properties/{property}', [Host\PropertyController::class, 'destroy'])->name('properties.destroy');
    Route::delete('/properties/{property}/images/{image}', [Host\PropertyController::class, 'destroyImage'])->name('properties.images.destroy');
    Route::post('/properties/{property}/images/{image}/cover', [Host\PropertyController::class, 'coverImage'])->name('properties.images.cover');
    Route::get('/properties/{property}/pricing', [Host\PropertyController::class, 'pricing'])->name('pricing');
    Route::post('/properties/{property}/pricing', [Host\PropertyController::class, 'pricingUpdate'])->name('pricing.update');
    Route::post('/properties/{property}/flash', [Host\PropertyController::class, 'flash'])->name('flash');

    // کاربران: ایجاد، ویرایش، حذف، ورود به‌جای کاربر
    Route::get('/users', [Admin\UserController::class, 'index'])->name('users');
    Route::get('/users/create', [Admin\UserController::class, 'create'])->name('users.create');
    Route::post('/users', [Admin\UserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}', [Admin\UserController::class, 'edit'])->name('users.edit');
    Route::post('/users/{user}', [Admin\UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [Admin\UserController::class, 'destroy'])->name('users.destroy');
    Route::post('/users/{user}/toggle', [Admin\ManageController::class, 'userToggle'])->name('users.toggle');
    Route::post('/users/{user}/verify', [Admin\ManageController::class, 'userVerify'])->name('users.verify');
    Route::post('/users/{user}/wallet', [Admin\UserController::class, 'wallet'])->name('users.wallet');
    Route::post('/users/{user}/impersonate', [Admin\UserController::class, 'impersonate'])->name('users.impersonate');
    Route::get('/users/{user}/id-photo', [Admin\UserController::class, 'idPhoto'])->name('users.idphoto');

    // اپلیکیشن اندروید
    Route::get('/app', [Admin\AppAdminController::class, 'index'])->name('app');
    Route::post('/app', [Admin\AppAdminController::class, 'store'])->name('app.store');
    Route::post('/app/stores', [Admin\AppAdminController::class, 'settings'])->name('app.stores');
    Route::post('/app/{release}/toggle', [Admin\AppAdminController::class, 'toggle'])->name('app.toggle');
    Route::delete('/app/{release}', [Admin\AppAdminController::class, 'destroy'])->name('app.destroy');
    Route::get('/app/{release}/file/{flavor}', [Admin\AppAdminController::class, 'file'])->name('app.file');

    // رزروها و پرداخت‌ها
    Route::get('/bookings', [Admin\ManageController::class, 'bookings'])->name('bookings');
    Route::get('/bookings/{booking}', [Admin\ManageController::class, 'booking'])->name('bookings.show');
    Route::post('/bookings/{booking}', [Admin\ManageController::class, 'bookingUpdate'])->name('bookings.update');
    Route::delete('/bookings/{booking}', [Admin\ManageController::class, 'bookingDestroy'])->name('bookings.destroy');
    Route::get('/payments', [Admin\ManageController::class, 'payments'])->name('payments');
    Route::post('/payments/{payment}/approve', [Admin\ManageController::class, 'cardApprove'])->name('payments.approve');
    Route::post('/payments/{payment}/reject', [Admin\ManageController::class, 'cardReject'])->name('payments.reject');
    Route::get('/payouts', [Admin\ManageController::class, 'payouts'])->name('payouts');
    Route::post('/payouts/{payout}', [Admin\ManageController::class, 'payoutUpdate'])->name('payouts.update');

    // نظرات
    Route::get('/reviews', [Admin\ManageController::class, 'reviews'])->name('reviews');
    Route::post('/reviews/{review}/approve', [Admin\ManageController::class, 'reviewApprove'])->name('reviews.approve');
    Route::post('/reviews/{review}/remove', [Admin\ManageController::class, 'reviewRemove'])->name('reviews.remove');
    Route::delete('/reviews/{review}', [Admin\ManageController::class, 'reviewDestroy'])->name('reviews.destroy');

    Route::get('/amenities', [Admin\ManageController::class, 'amenities'])->name('amenities');
    Route::post('/amenities', [Admin\ManageController::class, 'amenityStore'])->name('amenities.store');
    Route::post('/amenities/{amenity}', [Admin\ManageController::class, 'amenityUpdate'])->name('amenities.update');
    Route::delete('/amenities/{amenity}', [Admin\ManageController::class, 'amenityDestroy'])->name('amenities.destroy');

    Route::get('/coupons', [Admin\ManageController::class, 'coupons'])->name('coupons');
    Route::post('/coupons', [Admin\ManageController::class, 'couponStore'])->name('coupons.store');
    Route::post('/coupons/{coupon}/toggle', [Admin\ManageController::class, 'couponToggle'])->name('coupons.toggle');
    Route::delete('/coupons/{coupon}', [Admin\ManageController::class, 'couponDestroy'])->name('coupons.destroy');

    // تبلیغات میزبان‌ها
    Route::get('/ads', [Admin\AdsController::class, 'index'])->name('ads');
    Route::post('/ads/{ad}/approve', [Admin\AdsController::class, 'approve'])->name('ads.approve');
    Route::post('/ads/{ad}/reject', [Admin\AdsController::class, 'reject'])->name('ads.reject');

    // محتوا: صفحه‌ها و متن‌ها، کلمات ممنوع، پیامک‌ها، گزارش فعالیت، ارسال همگانی
    Route::get('/pages', [Admin\ContentController::class, 'pages'])->name('pages');
    Route::get('/pages/{slug}', [Admin\ContentController::class, 'pageEdit'])->name('pages.edit');
    Route::post('/pages/{slug}', [Admin\ContentController::class, 'pageUpdate'])->name('pages.update');
    Route::delete('/pages/{slug}', [Admin\ContentController::class, 'pageDestroy'])->name('pages.destroy');
    Route::get('/words', [Admin\ContentController::class, 'words'])->name('words');
    Route::post('/words', [Admin\ContentController::class, 'wordStore'])->name('words.store');
    Route::delete('/words/{word}', [Admin\ContentController::class, 'wordDestroy'])->name('words.destroy');
    Route::get('/sms', [Admin\ContentController::class, 'sms'])->name('sms');
    Route::get('/activity', [Admin\ContentController::class, 'activity'])->name('activity');
    Route::get('/broadcast', [Admin\ContentController::class, 'broadcast'])->name('broadcast');
    Route::post('/broadcast', [Admin\ContentController::class, 'broadcastSend'])->name('broadcast.send');
    Route::get('/export/{what}', [Admin\ContentController::class, 'export'])->name('export');
    Route::get('/backup', [Admin\ContentController::class, 'backup'])->name('backup');
    Route::get('/pool', [Admin\ContentController::class, 'pool'])->name('pool');

    Route::get('/settings', [Admin\ManageController::class, 'settings'])->name('settings');
    Route::post('/settings', [Admin\ManageController::class, 'settingsUpdate'])->name('settings.update');
    Route::post('/peaks', [Admin\ManageController::class, 'peakStore'])->name('peaks.store');
    Route::delete('/peaks/{peak}', [Admin\ManageController::class, 'peakDestroy'])->name('peaks.destroy');
});
