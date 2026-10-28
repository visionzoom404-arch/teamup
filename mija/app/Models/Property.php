<?php

namespace App\Models;

use App\Support\Pricing;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, BelongsToMany, HasMany};
use Illuminate\Support\Str;

class Property extends Model
{
    protected $guarded = [];

    protected $attributes = [
        'min_nights' => 1, 'max_nights' => 30, 'extra_guest_fee' => 0, 'cleaning_fee' => 0, 'weekly_discount' => 0, 'monthly_discount' => 0,
        'check_in_time' => '14:00', 'check_out_time' => '12:00', 'instant_booking' => false, 'status' => 'draft',
        'bedrooms' => 1, 'beds' => 1, 'bathrooms' => 1, 'rating_avg' => 0, 'reviews_count' => 0, 'views' => 0, 'featured' => false,
    ];

    protected function casts(): array
    {
        return ['instant_booking' => 'boolean', 'featured' => 'boolean', 'rating_avg' => 'float'];
    }

    protected static function booted(): void
    {
        static::creating(function (Property $p) {
            $p->slug ??= static::uniqueSlug($p->title);
        });
    }

    public static function uniqueSlug(string $title): string
    {
        $base = Str::slug(Str::limit($title, 50, ''), '-', 'fa') ?: 'villa';
        $slug = $base;
        $i = 1;
        while (static::where('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$i);
        }

        return $slug;
    }

    public function getRouteKeyName(): string { return 'slug'; }

    public function host(): BelongsTo { return $this->belongsTo(User::class, 'host_id'); }
    public function images(): HasMany { return $this->hasMany(PropertyImage::class)->orderBy('sort')->orderBy('id'); }
    public function amenities(): BelongsToMany { return $this->belongsToMany(Amenity::class); }
    public function bookings(): HasMany { return $this->hasMany(Booking::class); }
    public function reviews(): HasMany { return $this->hasMany(Review::class)->where('is_published', true)->latest('id'); }
    public function days(): HasMany { return $this->hasMany(CalendarDay::class); }

    public function scopeApproved(Builder $q): Builder { return $q->where('status', 'approved'); }

    /** ویلاهایی که در بازه‌ی [in, out) آزاد هستند */
    public function scopeAvailable(Builder $q, Carbon $in, Carbon $out): Builder
    {
        return $q->whereDoesntHave('bookings', fn ($b) => $b->active()->where('check_in', '<', $out->toDateString())->where('check_out', '>', $in->toDateString()))
            ->whereDoesntHave('days', fn ($d) => $d->where('is_blocked', true)->where('date', '>=', $in->toDateString())->where('date', '<', $out->toDateString()));
    }

    public function typeLabel(): string { return config('mija.property_types.'.$this->type, 'اقامتگاه'); }
    public function policy(): array { return config('mija.cancellation_policies.'.$this->cancellation_policy); }

    public function cover(): string
    {
        $img = $this->relationLoaded('images') ? $this->images->first() : $this->images()->first();

        return $img ? $img->url() : asset('img/placeholder.svg');
    }

    public function startingPrice(): int
    {
        return (int) min($this->price_weekday, $this->price_weekend);
    }

    public function isAvailable(Carbon $in, Carbon $out): bool
    {
        return ! $this->bookings()->active()->where('check_in', '<', $out->toDateString())->where('check_out', '>', $in->toDateString())->exists()
            && ! $this->days()->where('is_blocked', true)->where('date', '>=', $in->toDateString())->where('date', '<', $out->toDateString())->exists();
    }

    public function quote(Carbon $in, Carbon $out, int $guests, ?string $coupon = null): array
    {
        return Pricing::quote($this, $in, $out, $guests, $coupon);
    }
}
