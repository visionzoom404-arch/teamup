<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

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

    protected static function booted(): void
    {
        static::creating(function (User $u) {
            do {
                $u->referral_code = strtoupper(Str::random(6));
            } while (static::where('referral_code', $u->referral_code)->exists());
        });
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
    public function guestReviews(): HasMany { return $this->hasMany(GuestReview::class, 'guest_id')->latest('id'); }
    public function cashbackLots(): HasMany { return $this->hasMany(CashbackLot::class)->latest('id'); }
    public function serviceRequests(): HasMany { return $this->hasMany(ServiceRequest::class)->latest('id'); }
    public function ads(): HasMany { return $this->hasMany(Ad::class, 'host_id')->latest('id'); }
    public function favorites(): BelongsToMany { return $this->belongsToMany(Property::class, 'favorites')->withTimestamps(); }

    public function roleLabel(): string
    {
        return ['guest' => 'مهمان', 'host' => 'میزبان', 'admin' => 'مدیر سایت'][$this->role] ?? $this->role;
    }

    /** نام ویژگی رنگی نقش: blue / orange / dark */
    public function roleColor(): string
    {
        return ['guest' => 'blue', 'host' => 'orange', 'admin' => 'dark'][$this->role] ?? 'gray';
    }

    public function referredBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    /* ---------- مهمان: سطح وفاداری ---------- */

    public function completedNights(): int
    {
        return (int) $this->bookings()->where('status', 'completed')->sum('nights');
    }

    /** @return array{level: array, next: ?array, progress: int, nights: int} */
    public function level(): array
    {
        $nights = $this->completedNights();
        $levels = config('mija.levels');
        $cur = $levels[0];
        $next = null;
        foreach ($levels as $i => $l) {
            if ($nights >= $l['nights']) {
                $cur = $l;
                $next = $levels[$i + 1] ?? null;
            }
        }
        $progress = $next ? (int) min(100, round(($nights - $cur['nights']) / ($next['nights'] - $cur['nights']) * 100)) : 100;

        return ['level' => $cur, 'next' => $next, 'progress' => $progress, 'nights' => $nights];
    }

    /* ---------- میزبان: آمار، نرخ پاسخ، میزبان برتر ---------- */

    /** @return array{rating: float, reviews: int, completed: int, requests: int, response_rate: ?int, response_hours: ?float, accept_rate: ?int, superhost: bool} */
    public function hostStats(): array
    {
        return Cache::remember("mija.host.{$this->id}", 600, function () {
            $props = $this->properties()->where('status', 'approved');
            $reviews = (int) $props->sum('reviews_count');
            $rating = $reviews ? round((float) $this->properties()->where('reviews_count', '>', 0)->selectRaw('sum(rating_avg*reviews_count)/sum(reviews_count) as r')->value('r'), 2) : 0;
            $completed = $this->hostBookings()->where('status', 'completed')->count();

            $req = $this->hostBookings()->where(fn ($q) => $q->whereNotNull('respond_by')->orWhereNotNull('responded_at'));
            $requests = (clone $req)->count();
            $responded = (clone $req)->whereNotNull('responded_at')->get(['created_at', 'responded_at', 'status']);
            $rate = $requests ? (int) round($responded->count() / $requests * 100) : null;
            $hours = $responded->count() ? round($responded->avg(fn ($b) => $b->created_at->diffInMinutes($b->responded_at)) / 60, 1) : null;
            $accepted = $responded->filter(fn ($b) => $b->status !== 'rejected')->count();
            $acceptRate = $responded->count() ? (int) round($accepted / $responded->count() * 100) : null;

            return [
                'rating' => $rating, 'reviews' => $reviews, 'completed' => $completed, 'requests' => $requests,
                'response_rate' => $rate, 'response_hours' => $hours, 'accept_rate' => $acceptRate,
                'superhost' => $reviews >= 1 && $rating >= 4.7 && $completed >= 3 && ($acceptRate === null || $acceptRate >= 80),
            ];
        });
    }

    public function isSuperhost(): bool
    {
        return $this->isHost() && $this->hostStats()['superhost'];
    }

    /** چک‌لیست راه‌اندازی میزبان: [عنوان، انجام‌شده؟، لینک] */
    public function hostChecklist(): array
    {
        $props = $this->properties();
        $first = $props->first();

        return [
            ['ثبت اطلاعات هویتی', (bool) $this->national_code, route('host.onboard')],
            ['ثبت شماره کارت و شبا برای تسویه', (bool) ($this->sheba && $this->card_number), route('host.onboard')],
            ['تکمیل پروفایل (عکس و معرفی)', (bool) ($this->avatar && $this->bio), route('profile')],
            ['ثبت اولین اقامتگاه', (bool) $first, route('host.properties.create')],
            ['افزودن حداقل ۵ عکس', $this->properties()->withCount('images')->get()->contains(fn ($p) => $p->images_count >= 5), $first ? route('host.properties.edit', $first) : route('host.properties.create')],
            ['ارسال برای بررسی و تأیید', $this->properties()->where('status', 'approved')->exists(), route('host.properties.index')],
            ['تنظیم تقویم و قیمت ویژه', $first ? $first->days()->exists() : false, $first ? route('host.calendar', $first) : route('host.properties.create')],
        ];
    }

    public function avatarUrl(): string
    {
        return $this->avatar ? asset('storage/'.$this->avatar) : 'data:image/svg+xml;utf8,'.rawurlencode(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 80"><defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#3A2A1C"/><stop offset="1" stop-color="#B4692A"/></linearGradient></defs><rect width="80" height="80" fill="url(#g)"/><text x="40" y="53" font-size="34" text-anchor="middle" fill="#fff" font-family="sans-serif">'.e(mb_substr($this->name, 0, 1)).'</text></svg>'
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

    /** اعلان داخل سایت (و اپ)؛ با $sms = true همزمان پیامک (همراه لینک همان موضوع) هم ارسال می‌شود */
    public function notify(string $title, ?string $body = null, ?string $url = null, bool $sms = false, string $kind = 'system'): void
    {
        Notice::create(['user_id' => $this->id, 'title' => mb_substr($title, 0, 250), 'body' => $body ? mb_substr($body, 0, 250) : null, 'url' => $url, 'kind' => $kind]);
        if ($sms) {
            \App\Support\Sms::send($this->phone, $title.($body ? ' — '.$body : ''), $url);
        }
    }

    /** اعلان برای همه‌ی مدیران سایت (در بخش «اعلان‌ها»ی پنل مدیر) */
    public static function notifyAdmins(string $title, ?string $body = null, ?string $url = null, bool $sms = false): void
    {
        static::where('role', 'admin')->where('is_active', true)->get()->each(fn ($a) => $a->notify($title, $body, $url ?: route('admin.dashboard'), $sms && (int) setting('admin_sms_alerts'), 'admin'));
    }

    /* ---------- میزبان: احراز هویت و حساب بانکی ---------- */

    public function maskedCard(): ?string
    {
        return $this->card_number ? substr($this->card_number, 0, 4).' ●●●● ●●●● '.substr($this->card_number, -4) : null;
    }

    public function isOnboarded(): bool
    {
        return (bool) ($this->national_code && $this->sheba && $this->card_number && $this->host_onboarded_at);
    }

    /* ---------- مهمان: پیشینه و اقساط ---------- */

    public function guestRating(): ?float
    {
        return ($n = $this->guestReviews()->count()) ? round((float) $this->guestReviews()->avg('rating'), 1) : null;
    }

    public function commissionPercent(): int
    {
        return \App\Support\Pricing::commissionFor($this);
    }

    /** رزرو اقساطی: فقط برای مهمانی که امتیاز لازم را کسب کرده (صندوق پنهان کامل شده) */
    public function canInstallment(): bool
    {
        return (bool) setting('installment_enabled') && $this->installment_unlocked;
    }
}
