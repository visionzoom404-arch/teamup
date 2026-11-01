<?php

namespace App\Support;

use App\Models\Ad;
use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * تبلیغات میزبان: بنری، کلیکی، نردبان، پیامکی و اعلان.
 * هزینه هنگام ثبت از کیف پول میزبان کسر می‌شود و در صورت رد/عدم مصرف برگردانده می‌شود.
 */
class Ads
{
    public static function enabled(): bool { return (bool) (int) setting('ads_enabled'); }

    /** هزینه‌ی سفارش بر حسب نوع: [مبلغ کل، واحد] */
    public static function cost(string $type, int $units): array
    {
        return match ($type) {
            'banner' => [$units * (int) setting('ad_banner_day'), $units],
            'click' => [max($units, (int) setting('ad_click_min')), $units],
            'ladder' => [(int) setting('ad_ladder'), 1],
            'sms' => [$units * (int) setting('ad_sms_each'), $units],
            'push' => [$units * (int) setting('ad_push_each'), $units],
        };
    }

    public static function submit(User $host, array $d): Ad
    {
        abort_unless(self::enabled(), 403, 'بخش تبلیغات فعلاً غیرفعال است.');
        $type = $d['type'];
        $units = (int) ($d['units'] ?? 1);
        [$cost] = self::cost($type, $units);
        $prop = Property::where('id', $d['property_id'])->where('host_id', $host->id)->where('status', 'approved')->first();
        if (! $prop) {
            throw ValidationException::withMessages(['property_id' => 'اقامتگاه تأییدشده‌ای انتخاب کنید.']);
        }

        return DB::transaction(function () use ($host, $d, $type, $units, $cost, $prop) {
            $fresh = User::whereKey($host->id)->lockForUpdate()->first();
            if ($fresh->wallet < $cost) {
                throw ValidationException::withMessages(['type' => 'موجودی کیف پول کافی نیست؛ '.toman($cost).' لازم است.']);
            }
            $fresh->walletChange(-$cost, 'تبلیغات '.config("mija.ad_types.$type.0").' — '.$prop->title);
            $ad = Ad::create([
                'host_id' => $host->id, 'property_id' => $prop->id, 'type' => $type, 'title' => $d['title'] ?? $prop->title, 'text' => $d['text'] ?? null,
                'image' => $d['image'] ?? null, 'support_request' => (bool) ($d['support_request'] ?? false), 'support_text' => $d['support_text'] ?? null,
                'audience' => $d['audience'] ?? 'all', 'budget' => $cost, 'units' => $units,
                'starts_on' => $type === 'banner' ? ($d['starts_on'] ?? today()) : today(),
                'ends_on' => $type === 'banner' ? \Carbon\Carbon::parse($d['starts_on'] ?? today())->addDays(max(1, $units) - 1) : null,
                'status' => 'pending',
            ]);
            if ($type === 'ladder') {      // نردبان بلافاصله فعال می‌شود
                $prop->update(['ad_bump' => time()]);
                $ad->update(['status' => 'active', 'ends_on' => today()->addDay()]);
            }

            if ($ad->status === 'pending') {
                User::notifyAdmins($ad->support_request ? 'سفارش طراحی بنر از میزبان — نیاز به ساخت بنر' : 'تبلیغ جدید منتظر تأیید است', $ad->typeLabel().' — '.($ad->title ?: $prop->title), route('admin.ads'), true);
            }

            return $ad;
        });
    }

    public static function approve(Ad $ad): void
    {
        abort_unless($ad->status === 'pending', 422);
        if ($ad->type === 'banner' && $ad->support_request && ! $ad->image) {
            throw \Illuminate\Validation\ValidationException::withMessages(['ad' => 'این بنر سفارشی است؛ ابتدا تصویر بنر را برای میزبان بارگذاری کنید.']);
        }
        if (in_array($ad->type, ['sms', 'push'])) {
            self::dispatch($ad);

            return;
        }
        $ad->update(['status' => 'active', 'starts_on' => $ad->starts_on ?? today(), 'ends_on' => $ad->type === 'click' ? today()->addDays(30) : $ad->ends_on]);
        $ad->host->notify('تبلیغ شما تأیید و فعال شد', $ad->typeLabel().' — '.$ad->title, route('host.ads'), true, event: 'ad_status_host', vars: ['type' => $ad->typeLabel(), 'status' => 'تأیید و فعال شد']);
    }

    public static function reject(Ad $ad, ?string $note = null): void
    {
        abort_unless(in_array($ad->status, ['pending', 'active', 'paused']), 422);
        $left = max(0, $ad->budget - $ad->spent);
        DB::transaction(function () use ($ad, $note, $left) {
            if ($left > 0) {
                $ad->host->walletChange($left, 'بازگشت هزینه‌ی تبلیغ رد شده');
            }
            $ad->update(['status' => 'rejected', 'admin_note' => $note, 'spent' => $ad->budget]);
        });
        $ad->host->notify('تبلیغ شما تأیید نشد', $note, route('host.ads'), true, event: 'ad_status_host', vars: ['type' => $ad->typeLabel(), 'status' => 'تأیید نشد']);
    }

    /** توقف تبلیغ توسط خود میزبان؛ باقی‌مانده‌ی بودجه برمی‌گردد */
    public static function stop(Ad $ad): void
    {
        $left = max(0, $ad->budget - $ad->spent);
        DB::transaction(function () use ($ad, $left) {
            if ($left > 0 && $ad->type !== 'ladder') {
                $ad->host->walletChange($left, 'بازگشت باقی‌مانده‌ی بودجه‌ی تبلیغ');
            }
            $ad->update(['status' => 'finished', 'spent' => $ad->budget]);
        });
    }

    /** ارسال پیامک/اعلان تبلیغاتی به مهمانان هدف */
    public static function dispatch(Ad $ad): void
    {
        $q = User::where('role', 'guest')->where('is_active', true);
        if ($ad->audience && $ad->audience !== 'all' && ($rg = config('mija.regions.'.$ad->audience))) {
            $q->whereHas('favorites', fn ($f) => $f->whereIn('province', $rg['provinces']));
        }
        $guests = $q->limit($ad->units)->get();
        $url = route('property', $ad->property);
        $text = ($ad->title ? $ad->title.' — ' : '').($ad->text ?: $ad->property->title);
        foreach ($guests as $g) {
            $ad->type === 'sms' ? Sms::event('ad_promo', $g->phone, ['text' => $text], $url) : $g->notify($ad->title ?: 'پیشنهاد ویژه', $ad->text ?: $ad->property->title, $url);
        }
        $each = $ad->type === 'sms' ? (int) setting('ad_sms_each') : (int) setting('ad_push_each');
        $spent = $guests->count() * $each;
        $left = max(0, $ad->budget - $spent);
        DB::transaction(function () use ($ad, $guests, $spent, $left) {
            if ($left > 0) {
                $ad->host->walletChange($left, 'بازگشت مابه‌التفاوت تبلیغ (مخاطبِ کمتر از سفارش)');
            }
            $ad->update(['status' => 'finished', 'sent' => $guests->count(), 'spent' => $spent, 'starts_on' => today(), 'ends_on' => today()]);
        });
        $ad->host->notify('تبلیغ شما ارسال شد', 'برای '.fa($guests->count()).' نفر', route('host.ads'));
    }

    /** آگهی‌های بنری فعال امروز */
    public static function banners()
    {
        return Ad::live()->where('type', 'banner')->where(fn ($w) => $w->whereNull('starts_on')->orWhereDate('starts_on', '<=', today()))
            ->with('property.images')->whereHas('property', fn ($p) => $p->approved())->inRandomOrder()->take(10)->get();
    }

    /** اقامتگاه‌های کلیکی (اسپانسر) که هنوز بودجه دارند */
    public static function sponsored(int $n = 2)
    {
        return Ad::live()->where('type', 'click')->whereColumn('spent', '<', 'budget')->with(['property.images', 'property.host'])
            ->whereHas('property', fn ($p) => $p->approved())->inRandomOrder()->take($n)->get();
    }

    public static function click(Ad $ad): void
    {
        $cpc = (int) setting('ad_click_cpc');
        $ad->increment('clicks');
        if ($ad->type === 'click') {
            $ad->increment('spent', $cpc);
            if ($ad->fresh()->spent + $cpc > $ad->budget) {
                $ad->update(['status' => 'finished']);
                $ad->host->notify('بودجه‌ی تبلیغ کلیکی شما تمام شد', $ad->title, route('host.ads'));
            }
        }
    }

    public static function tick(): int
    {
        $n = 0;
        foreach (Ad::whereIn('status', ['active'])->whereNotNull('ends_on')->whereDate('ends_on', '<', today())->get() as $ad) {
            $left = max(0, $ad->budget - $ad->spent);
            if ($ad->type === 'click' && $left > 0) {
                $ad->host->walletChange($left, 'بازگشت بودجه‌ی مصرف‌نشده‌ی تبلیغ کلیکی');
            }
            $ad->update(['status' => 'finished']);
            $n++;
        }

        return $n;
    }
}
