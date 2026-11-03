<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $guarded = [];

    protected $casts = ['published_at' => 'datetime'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('status', 'published')->where(fn ($x) => $x->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }

    public function url(): string
    {
        return route('blog.show', $this->slug);
    }

    public function imageUrl(): string
    {
        if (! $this->image) {
            return asset('img/og.png');
        }

        return str_starts_with($this->image, 'img/') ? asset($this->image) : asset('storage/'.$this->image);
    }

    public function readMinutes(): int
    {
        return max(1, (int) ceil(str_word_count(strip_tags($this->body), 0, 'آابپتثجچحخدذرزژسشصضطظعغفقکگلمنوهیئءةۀ') / 200));
    }

    /** HTML امن‌سازی‌شده: فقط تگ‌های متنی، بدون script/on* */
    public static function clean(string $html): string
    {
        $html = strip_tags($html, '<h2><h3><h4><p><ul><ol><li><strong><b><em><i><a><br><table><thead><tbody><tr><th><td><blockquote><img><figure><figcaption>');
        $html = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);
        $html = preg_replace('/(href|src)\s*=\s*(["\'])\s*javascript:[^"\']*\2/i', '$1=$2#$2', $html);

        return $html;
    }

    /** بدنه با شناسه‌ی عنوان‌ها + فهرست مطالب خودکار */
    public function rendered(): array
    {
        $toc = [];
        $i = 0;
        $html = preg_replace_callback('~<h2([^>]*)>(.*?)</h2>~su', function ($m) use (&$toc, &$i) {
            $i++;
            $text = trim(strip_tags($m[2]));
            $toc[] = ['id' => 's'.$i, 'text' => $text];

            return '<h2 id="s'.$i.'">'.$m[2].'</h2>';
        }, $this->body);

        return [$html, $toc];
    }

    /** مقاله‌های مرتبط: هم‌دسته، بعد جدیدترین */
    public function related(int $n = 3)
    {
        return static::published()->where('id', '!=', $this->id)->orderByRaw('category = ? desc', [$this->category])->latest('published_at')->limit($n)->get();
    }
}
