<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany, HasOne};
use Illuminate\Support\Str;

class Booking extends Model
{
    public const ACTIVE = ['pending_host', 'awaiting_payment', 'confirmed', 'completed'];

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'check_in' => 'date:Y-m-d', 'check_out' => 'date:Y-m-d', 'breakdown' => 'array',
            'respond_by' => 'datetime', 'pay_by' => 'datetime', 'paid_at' => 'datetime',
            'settled_at' => 'datetime', 'cancelled_at' => 'datetime',
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
