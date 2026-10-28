<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\GatewayController;
use App\Http\Controllers\Guest;
use App\Http\Controllers\Host;
use App\Http\Controllers\NoticeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicController;
use Illuminate\Support\Facades\Route;

/* ---------- عمومی ---------- */
Route::get('/', [PublicController::class, 'home'])->name('home');
Route::get('/search', [PublicController::class, 'search'])->name('search');
Route::get('/p/{property}', [PublicController::class, 'show'])->name('property');
Route::get('/p/{property}/quote', [PublicController::class, 'quote'])->name('property.quote');
Route::get('/page/{slug}', [PublicController::class, 'page'])->name('page');
Route::get('/offline', fn () => view('offline'))->name('offline');

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
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/password', [ProfileController::class, 'password'])->name('profile.password');
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
    Route::get('/favorites', [Guest\PanelController::class, 'favorites'])->name('favorites');
    Route::get('/wallet', [Guest\PanelController::class, 'wallet'])->name('wallet');
    Route::post('/wallet/topup', [Guest\PanelController::class, 'topup'])->name('wallet.topup');
});

/* ---------- پنل میزبان ---------- */
Route::middleware(['auth', 'role:host'])->prefix('host')->name('host.')->group(function () {
    Route::get('/', [Host\PanelController::class, 'dashboard'])->name('dashboard');
    Route::get('/properties', [Host\PropertyController::class, 'index'])->name('properties.index');
    Route::get('/properties/create', [Host\PropertyController::class, 'create'])->name('properties.create');
    Route::post('/properties', [Host\PropertyController::class, 'store'])->name('properties.store');
    Route::get('/properties/{property}/edit', [Host\PropertyController::class, 'edit'])->name('properties.edit');
    Route::post('/properties/{property}', [Host\PropertyController::class, 'update'])->name('properties.update');
    Route::post('/properties/{property}/submit', [Host\PropertyController::class, 'submit'])->name('properties.submit');
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

    Route::get('/reviews', [Host\PanelController::class, 'reviews'])->name('reviews');
    Route::post('/reviews/{review}/reply', [Host\PanelController::class, 'reply'])->name('reviews.reply');
    Route::get('/wallet', [Host\PanelController::class, 'wallet'])->name('wallet');
    Route::post('/wallet/payout', [Host\PanelController::class, 'payout'])->name('wallet.payout');
    Route::get('/coupons', [Host\PanelController::class, 'coupons'])->name('coupons');
    Route::post('/coupons', [Host\PanelController::class, 'couponStore'])->name('coupons.store');
    Route::delete('/coupons/{coupon}', [Host\PanelController::class, 'couponDestroy'])->name('coupons.destroy');
});

/* ---------- پنل مدیریت ---------- */
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');
    Route::post('/tick', [Admin\DashboardController::class, 'tick'])->name('tick');

    Route::get('/properties', [Admin\ManageController::class, 'properties'])->name('properties');
    Route::get('/properties/{property}', [Admin\ManageController::class, 'property'])->name('properties.show');
    Route::post('/properties/{property}/status', [Admin\ManageController::class, 'propertyStatus'])->name('properties.status');
    Route::post('/properties/{property}/feature', [Admin\ManageController::class, 'propertyFeature'])->name('properties.feature');

    Route::get('/users', [Admin\ManageController::class, 'users'])->name('users');
    Route::post('/users/{user}/toggle', [Admin\ManageController::class, 'userToggle'])->name('users.toggle');
    Route::post('/users/{user}/verify', [Admin\ManageController::class, 'userVerify'])->name('users.verify');

    Route::get('/bookings', [Admin\ManageController::class, 'bookings'])->name('bookings');
    Route::get('/bookings/{booking}', [Admin\ManageController::class, 'booking'])->name('bookings.show');
    Route::get('/payments', [Admin\ManageController::class, 'payments'])->name('payments');
    Route::get('/payouts', [Admin\ManageController::class, 'payouts'])->name('payouts');
    Route::post('/payouts/{payout}', [Admin\ManageController::class, 'payoutUpdate'])->name('payouts.update');

    Route::get('/reviews', [Admin\ManageController::class, 'reviews'])->name('reviews');
    Route::post('/reviews/{review}/toggle', [Admin\ManageController::class, 'reviewToggle'])->name('reviews.toggle');

    Route::get('/amenities', [Admin\ManageController::class, 'amenities'])->name('amenities');
    Route::post('/amenities', [Admin\ManageController::class, 'amenityStore'])->name('amenities.store');
    Route::delete('/amenities/{amenity}', [Admin\ManageController::class, 'amenityDestroy'])->name('amenities.destroy');

    Route::get('/coupons', [Admin\ManageController::class, 'coupons'])->name('coupons');
    Route::post('/coupons', [Admin\ManageController::class, 'couponStore'])->name('coupons.store');
    Route::post('/coupons/{coupon}/toggle', [Admin\ManageController::class, 'couponToggle'])->name('coupons.toggle');

    Route::get('/settings', [Admin\ManageController::class, 'settings'])->name('settings');
    Route::post('/settings', [Admin\ManageController::class, 'settingsUpdate'])->name('settings.update');
    Route::post('/peaks', [Admin\ManageController::class, 'peakStore'])->name('peaks.store');
    Route::delete('/peaks/{peak}', [Admin\ManageController::class, 'peakDestroy'])->name('peaks.destroy');
});
