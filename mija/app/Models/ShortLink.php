<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ShortLink extends Model
{
    protected $guarded = [];

    /** آدرس کوتاه‌شده‌ی یک لینک؛ همان لینک همیشه همان کد را می‌گیرد */
    public static function make(string $url): string
    {
        $hash = sha1($url);
        $row = static::where('hash', $hash)->first();
        if (! $row) {
            do {
                $code = Str::lower(Str::random(6));
            } while (static::where('code', $code)->exists());
            $row = static::create(['code' => $code, 'hash' => $hash, 'url' => $url]);
        }

        return url('/s/'.$row->code);
    }
}
