<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany, HasOne};
use Illuminate\Support\Str;

class Booking extends Model
{
    public const ACTIVE = ['pending_host', 'awaiting_payment', 'payment_review', 'confirmed', 'completed'];

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'check_in' => 'date:Y-m-d', 'check_out' => 'date:Y-m-d', 'breakdown' => 'array',
            'respond_by' => 'datetime', 'pay_by' => 'datetime', 'paid_at' => 'datetime',
            'settled_at' => 'datetime', 'cancelled_at' => 'datetime', 'offer_check_in' => 'date:Y-m-d', 'offer_check_out' => 'date:Y-m-d',
            'offer_expires_at' => 'datetime', 'is_installment' => 'boolean', 'survey_sent_at' => 'datetime', 'reminded_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Booking $b) {
            do {
                $b->code = 'MJ-'.strtoupper(Str::random(6));
            } while (static::where('code', $b->code)->exists());
        });
    }

    public function getRouteKeyName(): string { return 'code'; }

    public function property(): BelongsTo { return $this->belongsTo(Property::class); }
    public function guest(): BelongsTo { return $this->belongsTo(User::class, 'guest_id'); }
    public function host(): BelongsTo { return $this->belongsTo(User::class, 'host_id'); }
    public function review(): HasOne { return $this->hasOne(Review::class); }
    public function messages(): HasMany { return $this->hasMany(Message::class)->oldest('id'); }
    public function payments(): HasMany { return $this->hasMany(Payment::class); }
    public function installments(): HasMany { return $this->hasMany(Installment::class)->orderBy('seq'); }
    public function serviceRequests(): HasMany { return $this->hasMany(ServiceRequest::class)->latest('id'); }
    public function guestReview(): HasOne { return $this->hasOne(GuestReview::class); }

    public function isPaid(): bool { return in_array($this->status, ['confirmed', 'completed'], true); }

    /** اطلاعات دقیق (آدرس، تلفن میزبان/مهمان) فقط پس از پرداخت موفق دیده می‌شود */
    public function detailsUnlocked(): bool { return $this->isPaid(); }

    /**
     * وضعیت نوار مراحل برای $who = guest|host: [شماره‌ی مرحله‌ی جاری، خراب شده؟]
     * مراحل: ۱ ثبت · ۲ تأیید میزبان · ۳ پرداخت · ۴ قطعی · ۵ اقامت · ۶ نظر / امتیاز
     */
    public function stepState(string $who = 'guest'): array
    {
        $end = $who === 'guest' ? (bool) $this->review : (bool) $this->guestReview;
        return match ($this->status) {
            'pending_host', 'offered' => [2, false],
            'awaiting_payment', 'payment_review' => [3, false],
            'confirmed' => [5, false],
            'completed' => [$end ? 7 : 6, false],
            'rejected' => [2, true],
            'expired' => [$this->responded_at ? 3 : 2, true],
            'cancelled_guest', 'cancelled_host', 'cancelled_system' => [$this->paid_at ? 5 : 3, true],
            default => [1, false],
        };
    }

    public function canRespondOffer(): bool
    {
        return $this->status === 'offered' && $this->offer_expires_at && $this->offer_expires_at->isFuture();
    }

    public function scopeActive(Builder $q): Builder { return $q->whereIn('status', self::ACTIVE); }

    public function statusLabel(): string { return config("mija.booking_statuses.{$this->status}.0", $this->status); }
    public function statusColor(): string { return config("mija.booking_statuses.{$this->status}.1", 'gray'); }

    public function guestsCount(): int { return $this->adults + $this->children; }

    public function canPay(): bool
    {
        return $this->status === 'awaiting_payment' && $this->pay_by && $this->pay_by->isFuture();
    }

    public function canReview(): bool
    {
        return $this->status === 'completed' && ! $this->review;
    }

    /** درصد قابل استرداد در صورت لغو توسط مهمان در این لحظه */
    public function refundPercent(?\Carbon\Carbon $now = null): int
    {
        $now ??= now();
        $daysLeft = $now->diffInDays($this->check_in->copy()->setTimeFromTimeString($this->property->check_in_time ?? '14:00'), false);
        foreach (config("mija.cancellation_policies.{$this->cancellation_policy}.tiers", []) as [$days, $pct]) {
            if ($daysLeft >= $days) {
                return $pct;
            }
        }

        return 0;
    }
}
