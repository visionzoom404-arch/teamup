<?php

namespace App\Console\Commands;

use App\Models\Property;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * جایگزینی تصاویر نمونه با عکس واقعی و «مجاز».
 *
 *  ۱) از پوشه:   php artisan mija:photos --dir=/path/photos
 *     ساختار: photos/<slug یا نوع اقامتگاه>/*.jpg  (مثلاً photos/villa/1.jpg یا photos/cabin/a.webp)
 *  ۲) از Unsplash (رایگان، مجوز تجاری):  php artisan mija:photos --unsplash=ACCESS_KEY
 *     طبق قوانین Unsplash عکس‌ها از سرور خودشان نمایش داده و نام عکاس زیر تصویر ذکر می‌شود.
 *
 * عکس‌ها را از منابعی بردارید که مجوز استفاده‌ی تجاری دارند (Unsplash, Pexels, Pixabay یا عکس‌های خودتان)؛
 * عکس‌های پینترست معمولاً حق‌نشر دارند و برداشتن خودکارشان قانونی نیست.
 */
class MijaPhotos extends Command
{
    protected $signature = 'mija:photos {--dir= : پوشه‌ی عکس‌ها} {--unsplash= : Access Key اپلیکیشن Unsplash} {--per=5 : تعداد عکس برای هر اقامتگاه} {--keep : نگه‌داشتن تصاویر فعلی}';

    protected $description = 'وارد کردن عکس‌های واقعی (مجاز) برای اقامتگاه‌ها';

    private const QUERY = [
        'villa' => 'luxury villa pool', 'cottage' => 'wooden cabin forest', 'garden_villa' => 'villa garden house',
        'suite' => 'modern hotel suite bedroom', 'eco' => 'traditional adobe house desert', 'apartment' => 'modern apartment interior',
        'farm' => 'countryside farmhouse', 'tiny' => 'a-frame cabin mountain',
    ];

    public function handle(): int
    {
        $per = max(1, (int) $this->option('per'));
        $dir = $this->option('dir');
        $key = $this->option('unsplash');
        if (! $dir && ! $key) {
            $this->error('یکی از گزینه‌های --dir یا --unsplash را بدهید. راهنما: php artisan mija:photos --help');

            return self::FAILURE;
        }
        if ($dir && ! File::isDirectory($dir)) {
            $this->error("پوشه پیدا نشد: $dir");

            return self::FAILURE;
        }

        if ($dir) {
            $this->siteImages($dir);
        }

        $props = Property::with('images')->orderBy('id')->get();
        $seen = [];
        foreach ($props as $p) {
            $idx = $seen[$p->type] = ($seen[$p->type] ?? -1) + 1;
            $added = $dir ? $this->fromDir($p, $dir, $per, $idx) : $this->fromUnsplash($p, $key, $per);
            if ($added && ! $this->option('keep')) {
                foreach ($p->images->filter(fn ($i) => str_ends_with($i->path, '.svg')) as $old) {
                    Storage::disk('public')->delete($old->path);
                    $old->delete();
                }
            }
            $this->line("• {$p->title}: ".$added.' عکس');
        }

        return self::SUCCESS;
    }

    private function isImage($f): bool
    {
        return in_array(strtolower($f->getExtension()), ['jpg', 'jpeg', 'png', 'webp'], true);
    }

    /** کوچک‌سازی و فشرده‌سازی تا سایت سریع بماند (حداکثر ضلع بزرگ ۱۸۰۰ پیکسل) */
    private function optimize(string $bytes, int $max = 1800, int $quality = 82): string
    {
        if (! function_exists('imagecreatefromstring') || ! ($im = @imagecreatefromstring($bytes))) {
            return $bytes;
        }
        [$w, $h] = [imagesx($im), imagesy($im)];
        if (max($w, $h) > $max) {
            $r = $max / max($w, $h);
            $im = imagescale($im, (int) round($w * $r), (int) round($h * $r)) ?: $im;
        }
        imageinterlace($im, true);
        ob_start();
        imagejpeg($im, null, $quality);

        return ob_get_clean() ?: $bytes;
    }

    /** تصاویر سراسری: hero.* و login.* در ریشه یا پوشه‌ی site */
    private function siteImages(string $dir): void
    {
        foreach (['hero', 'login'] as $name) {
            foreach ([$dir, "$dir/site"] as $base) {
                $f = collect(File::isDirectory($base) ? File::files($base) : [])->first(fn ($f) => strtolower($f->getFilenameWithoutExtension()) === $name && $this->isImage($f));
                if ($f) {
                    foreach (['jpg', 'webp', 'png'] as $e) {
                        Storage::disk('public')->delete("site/$name.$e");
                    }
                    Storage::disk('public')->put("site/$name.jpg", $this->optimize(File::get($f->getPathname()), 2200, 84));
                    $this->info("تصویر سایت: $name");
                    break;
                }
            }
        }
    }

    private function fromDir(Property $p, string $dir, int $per, int $idx): int
    {
        $dir = rtrim($dir, '/');
        $label = config('mija.property_types.'.$p->type);
        $pool = null;
        $own = false;
        $alias = in_array($p->type, ['cottage', 'tiny'], true) ? ['cabin'] : [];
        foreach (array_merge([$p->slug, $p->type, $label], $alias, ['all', 'photos']) as $sub) {
            if ($sub && File::isDirectory("$dir/$sub")) {
                $files = collect(File::files("$dir/$sub"))->filter(fn ($f) => $this->isImage($f))->sortBy(fn ($f) => $f->getFilename())->values();
                if ($files->isNotEmpty()) {
                    $pool = $files;
                    $own = $sub === $p->slug;
                    break;
                }
            }
        }
        // عکس‌های ریشه (غیر از hero/login)
        $pool ??= collect(File::files($dir))->filter(fn ($f) => $this->isImage($f) && ! in_array(strtolower($f->getFilenameWithoutExtension()), ['hero', 'login'], true))->sortBy(fn ($f) => $f->getFilename())->values();
        if ($pool->isEmpty()) {
            return 0;
        }

        $offset = $own ? 0 : ($idx * $per) % $pool->count();
        $n = 0;
        $sort = (int) $p->images()->max('sort');
        for ($i = 0; $i < min($per, $pool->count()); $i++) {
            $f = $pool[($offset + $i) % $pool->count()];
            $dest = "properties/{$p->id}/".uniqid().'.jpg';
            Storage::disk('public')->put($dest, $this->optimize(File::get($f->getPathname())));
            $p->images()->create(['path' => $dest, 'sort' => ++$sort]);
            $n++;
        }

        return $n;
    }

    private function fromUnsplash(Property $p, string $key, int $per): int
    {
        $res = Http::timeout(20)->get('https://api.unsplash.com/search/photos', [
            'query' => self::QUERY[$p->type] ?? 'holiday villa', 'per_page' => $per, 'orientation' => 'landscape', 'page' => 1 + $p->id % 3, 'client_id' => $key,
        ]);
        if (! $res->ok()) {
            $this->warn('Unsplash: '.$res->status().' '.$res->body());

            return 0;
        }
        $n = 0;
        $sort = (int) $p->images()->max('sort');
        foreach ($res->json('results', []) as $ph) {
            $p->images()->create([
                'path' => $ph['urls']['regular'] ?? $ph['urls']['small'], 'sort' => ++$sort,
                'credit' => $ph['user']['name'] ?? 'Unsplash', 'credit_url' => ($ph['user']['links']['html'] ?? 'https://unsplash.com').'?utm_source=mija&utm_medium=referral',
            ]);
            // طبق قوانین API: ثبت «دانلود»
            if (! empty($ph['links']['download_location'])) {
                Http::timeout(10)->get($ph['links']['download_location'], ['client_id' => $key]);
            }
            $n++;
        }

        return $n;
    }
}
