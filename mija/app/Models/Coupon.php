<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $guarded = [];

    protected function casts(): array { return ['expires_at' => 'datetime', 'is_active' => 'boolean']; }

    public function isUsable(int $amount, ?int $hostId = null): bool
    {
        return $this->is_active
            && (! $this->expires_at || $this->expires_at->isFuture())
            && (! $this->usage_limit || $this->used_count < $this->usage_limit)
            && $amount >= $this->min_amount
            && (! $this->host_id || $this->host_id === $hostId);
    }

    public function discountFor(int $amount): int
    {
        $d = $this->type === 'percent' ? intdiv($amount * $this->value, 100) : $this->value;
        if ($this->max_discount) {
            $d = min($d, $this->max_discount);
        }

        return min($d, $amount);
    }
}
