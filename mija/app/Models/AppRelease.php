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
     * نسخه‌های همراه بسته‌ی نصب (storage/app/apk/release.json) را در دیتابیس ثبت می‌کند.
     * ساخته‌شده توسط android/build.sh (و deploy/build-release.sh).
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
        $r = static::firstOrNew(['version_code' => (int) $j['version_code']]);
        if ($r->exists && $r->direct_file === ($j['direct']['file'] ?? null) && $r->direct_sha256 === ($j['direct']['sha256'] ?? null)) {
            return 0;
        }
        $r->fill([
            'version_name' => $j['version_name'],
            'changelog' => $j['changelog'] ?? [],
            'direct_file' => $j['direct']['file'] ?? null, 'direct_size' => $j['direct']['size'] ?? 0, 'direct_sha256' => $j['direct']['sha256'] ?? null,
            'store_file' => $j['store']['file'] ?? null, 'store_size' => $j['store']['size'] ?? 0, 'store_sha256' => $j['store']['sha256'] ?? null,
        ]);
        $r->published = $r->exists ? $r->published : true;
        $r->save();

        return 1;
    }
}
