<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppRelease extends Model
{
    protected $guarded = [];

    protected $casts = ['changelog' => 'array', 'force' => 'boolean', 'published' => 'boolean'];

    public const PACKAGE = 'click.mija.app';

    protected static function booted(): void
    {
        $forget = fn () => \Illuminate\Support\Facades\Cache::forget('mija.app.exists');
        static::saved($forget);
        static::deleted($forget);
    }

    public static function dir(): string
    {
        return storage_path('app/apk');
    }

    /** آیا نسخه‌ی منتشرشده‌ای وجود دارد؟ (کش ۱۰ دقیقه‌ای برای بنر همه‌ی صفحه‌ها) */
    public static function exists(): bool
    {
        return (bool) \Illuminate\Support\Facades\Cache::remember('mija.app.exists', 600, fn () => static::latest() !== null);
    }

    public static function latest(): ?self
    {
        return static::where('published', true)->whereNotNull('direct_file')->orderByDesc('version_code')->first();
    }

    public function path(string $flavor = 'direct'): ?string
    {
        $f = $flavor === 'store' ? $this->store_file : $this->direct_file;

        return $f && is_file(static::dir().'/'.$f) ? static::dir().'/'.$f : null;
    }

    /**
     * نسخه‌ی همراه بسته (storage/app/apk/release.json) را خودکار در دیتابیس ثبت می‌کند؛
     * با هر نصب/به‌روزرسانی سایت و همچنین هر دقیقه توسط زمان‌بند (mija:tick) اجرا می‌شود.
     * برای نسخه‌ی تازه، به کاربران اپ اعلان «نسخه‌ی جدید رسید» می‌رود.
     */
    public static function sync(): int
    {
        $meta = static::dir().'/release.json';
        if (! is_file($meta)) {
            return 0;
        }
        $j = json_decode((string) file_get_contents($meta), true);
        if (! is_array($j) || empty($j['version_code'])) {
            return 0;
        }
        // لینک فروشگاه‌ها (اختیاری) از همین فایل
        foreach (['bazaar' => 'app_bazaar_url', 'myket' => 'app_myket_url'] as $k => $key) {
            $url = trim((string) ($j['stores'][$k] ?? ''));
            if ($url !== '' && $url !== setting($key)) {
                Setting::put($key, $url);
            }
        }
        $r = static::firstOrNew(['version_code' => (int) $j['version_code']]);
        $same = $r->exists && $r->direct_file === ($j['direct']['file'] ?? null) && $r->direct_sha256 === ($j['direct']['sha256'] ?? null);
        if ($same) {
            return 0;
        }
        $isNew = ! $r->exists;
        $r->fill([
            'version_name' => $j['version_name'],
            'changelog' => $j['changelog'] ?? [],
            'force' => (bool) ($j['force'] ?? $r->force),
            'direct_file' => $j['direct']['file'] ?? null, 'direct_size' => $j['direct']['size'] ?? 0, 'direct_sha256' => $j['direct']['sha256'] ?? null,
            'store_file' => $j['store']['file'] ?? null, 'store_size' => $j['store']['size'] ?? 0, 'store_sha256' => $j['store']['sha256'] ?? null,
        ]);
        $r->published = $r->exists ? $r->published : true;
        $r->save();
        if ($isNew && static::latest()?->id === $r->id) {
            static::announce($r);
        }

        return 1;
    }

    /** اعلان درون‌سایت برای کاربرانی که با نسخه‌ی قدیمی‌تر اپ را استفاده می‌کنند */
    protected static function announce(self $r): void
    {
        $url = route('app.update');
        User::whereNotNull('app_code')->where('app_code', '<', $r->version_code)->select('id')->chunkById(200, function ($users) use ($r, $url) {
            $now = now();
            Notice::insert($users->map(fn ($u) => [
                'user_id' => $u->id, 'title' => 'نسخه‌ی جدید اپلیکیشن می‌جا رسید',
                'body' => 'نسخه‌ی '.fa($r->version_name).' آماده است؛ همین حالا به‌روزرسانی کنید.', 'url' => $url,
                'created_at' => $now, 'updated_at' => $now,
            ])->all());
        });
    }
}
