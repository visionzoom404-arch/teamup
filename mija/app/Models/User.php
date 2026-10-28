<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $guarded = [];

    protected $attributes = ['role' => 'guest', 'wallet' => 0, 'is_active' => true, 'host_verified' => false];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['password' => 'hashed', 'is_active' => 'boolean', 'host_verified' => 'boolean', 'phone_verified_at' => 'datetime'];
    }

    public function isAdmin(): bool { return $this->role === 'admin'; }
    public function isHost(): bool { return $this->role === 'host'; }
    public function isGuest(): bool { return $this->role === 'guest'; }

    public function homeRoute(): string
    {
        return match ($this->role) { 'admin' => route('admin.dashboard'), 'host' => route('host.dashboard'), default => route('guest.dashboard') };
    }

    public function properties(): HasMany { return $this->hasMany(Property::class, 'host_id'); }
    public function bookings(): HasMany { return $this->hasMany(Booking::class, 'guest_id'); }
    public function hostBookings(): HasMany { return $this->hasMany(Booking::class, 'host_id'); }
    public function transactions(): HasMany { return $this->hasMany(WalletTransaction::class)->latest('id'); }
    public function notices(): HasMany { return $this->hasMany(Notice::class)->latest('id'); }
    public function payouts(): HasMany { return $this->hasMany(Payout::class, 'host_id')->latest('id'); }
    public function favorites(): BelongsToMany { return $this->belongsToMany(Property::class, 'favorites')->withTimestamps(); }

    public function avatarUrl(): string
    {
        return $this->avatar ? asset('storage/'.$this->avatar) : 'data:image/svg+xml;utf8,'.rawurlencode(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 80"><defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#FF3B3B"/><stop offset="1" stop-color="#FF9A1F"/></linearGradient></defs><rect width="80" height="80" fill="url(#g)"/><text x="40" y="53" font-size="36" text-anchor="middle" fill="#fff" font-family="sans-serif">'.e(mb_substr($this->name, 0, 1)).'</text></svg>'
        );
    }

    /** افزودن/کسر از کیف پول به‌صورت ایمن و ثبت تراکنش */
    public function walletChange(int $amount, string $reason, ?Booking $booking = null): WalletTransaction
    {
        return \DB::transaction(function () use ($amount, $reason, $booking) {
            $u = static::whereKey($this->id)->lockForUpdate()->first();
            $u->wallet += $amount;
            $u->save();
            $this->wallet = $u->wallet;

            return $u->transactions()->create([
                'booking_id' => $booking?->id,
                'type' => $amount >= 0 ? 'credit' : 'debit',
                'amount' => abs($amount),
                'balance_after' => $u->wallet,
                'reason' => $reason,
            ]);
        });
    }

    public function notify(string $title, ?string $body = null, ?string $url = null): void
    {
        Notice::create(['user_id' => $this->id, 'title' => $title, 'body' => $body, 'url' => $url]);
    }
}
