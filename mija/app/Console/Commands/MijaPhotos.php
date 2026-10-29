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

        foreach (Property::with('images')->get() as $p) {
            $added = $dir ? $this->fromDir($p, $dir, $per) : $this->fromUnsplash($p, $key, $per);
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

    private function fromDir(Property $p, string $dir, int $per): int
    {
        foreach ([$p->slug, $p->type, 'all'] as $sub) {
            $path = rtrim($dir, '/')."/$sub";
            if (File::isDirectory($path)) {
                $files = collect(File::files($path))->filter(fn ($f) => in_array(strtolower($f->getExtension()), ['jpg', 'jpeg', 'png', 'webp']))->values();
                if ($files->isEmpty()) {
                    continue;
                }
                // برای نوع‌های مشترک، عکس‌ها بین اقامتگاه‌ها چرخش می‌کنند
                $offset = $sub === $p->slug ? 0 : ($p->id * 3) % $files->count();
                $n = 0;
                $sort = (int) $p->images()->max('sort');
                for ($i = 0; $i < min($per, $files->count()); $i++) {
                    $f = $files[($offset + $i) % $files->count()];
                    $dest = "properties/{$p->id}/".uniqid().'.'.strtolower($f->getExtension());
                    Storage::disk('public')->put($dest, File::get($f->getPathname()));
                    $p->images()->create(['path' => $dest, 'sort' => ++$sort]);
                    $n++;
                }

                return $n;
            }
        }

        return 0;
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
