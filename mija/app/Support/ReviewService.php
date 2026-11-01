<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\Review;
use App\Models\User;

/**
 * چرخه‌ی نظر: مهمان می‌نویسد ← میزبان می‌خواند و تأیید/رد می‌کند ←
 * رد شده‌ها برای مدیر می‌روند ← مدیر منتشر یا حذف می‌کند.
 */
class ReviewService
{
    public static function submit(Booking $b, array $d, array $photos = []): Review
    {
        $paths = [];
        foreach (array_slice($photos, 0, 4) as $f) {
            $paths[] = $f->store('reviews/'.$b->id, 'public');
        }
        $r = Review::create($d + ['booking_id' => $b->id, 'property_id' => $b->property_id, 'guest_id' => $b->guest_id, 'status' => 'pending_host', 'photos' => $paths ?: null, 'is_published' => false]);
        $b->host->notify('نظر جدید برای اقامتگاه شما — بخوانید و تأیید کنید', $b->property->title, route('host.reviews'), true, event: 'review_new_host', vars: ['property' => $b->property->title]);

        return $r;
    }

    public static function publish(Review $r): void
    {
        $r->update(['status' => 'published', 'is_published' => true, 'published_at' => now()]);
        $p = $r->property;
        $p->update(['rating_avg' => round((float) $p->reviews()->avg('rating'), 2), 'reviews_count' => $p->reviews()->count()]);
        $r->guest->notify('نظر شما منتشر شد', $p->title, route('property', $p), true, event: 'review_published_guest', vars: ['property' => $p->title]);
        Cashback::award($r);
        \Illuminate\Support\Facades\Cache::forget("mija.host.{$p->host_id}");
    }

    public static function hostApprove(Review $r, ?string $reply = null): void
    {
        abort_unless($r->status === 'pending_host', 422);
        $r->update($reply ? ['host_reply' => $reply] : []);
        self::publish($r);
    }

    public static function hostReject(Review $r, string $reason): void
    {
        abort_unless($r->status === 'pending_host', 422);
        $r->update(['status' => 'host_rejected', 'host_reject_reason' => $reason]);
        User::where('role', 'admin')->get()->each(fn ($a) => $a->notify('نظر ردشده توسط میزبان — نیاز به بررسی', $r->property->title, route('admin.reviews', ['tab' => 'flagged'])));
    }

    public static function adminApprove(Review $r): void
    {
        abort_unless(in_array($r->status, ['host_rejected', 'removed', 'pending_host']), 422);
        self::publish($r);
    }

    public static function adminRemove(Review $r, ?string $note = null): void
    {
        $r->update(['status' => 'removed', 'is_published' => false, 'admin_note' => $note]);
        $p = $r->property;
        $p->update(['rating_avg' => round((float) $p->reviews()->avg('rating'), 2), 'reviews_count' => $p->reviews()->count()]);
        $r->guest->notify('نظر شما منتشر نشد', $note ?: 'متن یا تصاویر با قوانین مطابقت نداشت.', route('guest.bookings.show', $r->booking));
    }
}
