<?php

namespace App\Support;

use App\Models\BannedWord;
use Illuminate\Support\Facades\Cache;

/** فیلتر کلمات رکیک و ممنوع؛ کلمات پیش‌فرض + کلمات افزوده‌شده توسط مدیر */
class Profanity
{
    public const DEFAULTS = [
        'کیر', 'کص', 'کس', 'کون', 'کونی', 'جنده', 'جاکش', 'کسکش', 'کصکش', 'مادرجنده', 'مادرقحبه', 'قحبه', 'حرومزاده', 'حرامزاده',
        'گایید', 'گاییدم', 'گائیدم', 'بکنمت', 'کیرم', 'کسخل', 'کسشر', 'کصشر', 'ننتو', 'خارکسه', 'خارکصه', 'پدرسگ', 'پدرسوخته',
        'بیناموس', 'بی‌ناموس', 'عوضی', 'لعنتی', 'دیوث', 'ممه', 'فاحشه', 'گوه', 'گه', 'تخمی', 'تخم', 'بیشرف', 'بی‌شرف', 'کثافت',
        'fuck', 'shit', 'bitch', 'asshole', 'dick', 'pussy',
    ];

    public static function normalize(string $s): string
    {
        $s = strtr($s, ['ي' => 'ی', 'ك' => 'ک', 'ى' => 'ی', 'ة' => 'ه', 'ۀ' => 'ه', 'أ' => 'ا', 'إ' => 'ا', 'ؤ' => 'و']);
        $s = preg_replace('/[\x{200C}\x{200D}\x{064B}-\x{065F}\x{0640}]/u', '', $s); // نیم‌فاصله، اعراب، کشیده

        return mb_strtolower($s);
    }

    public static function words(): array
    {
        return Cache::remember('mija.banned', 300, fn () => array_values(array_unique(array_merge(
            array_map([self::class, 'normalize'], self::DEFAULTS),
            BannedWord::pluck('word')->map(fn ($w) => self::normalize($w))->all()
        ))));
    }

    public static function forget(): void { Cache::forget('mija.banned'); }

    /** اولین کلمه‌ی ممنوعِ موجود در متن یا null */
    public static function find(?string $text): ?string
    {
        if (! $text) {
            return null;
        }
        $n = self::normalize($text);
        $tokens = preg_split('/[^\p{L}\p{N}]+/u', $n, -1, PREG_SPLIT_NO_EMPTY);
        $glued = implode('', $tokens);
        foreach (self::words() as $w) {
            if ($w === '') {
                continue;
            }
            if (mb_strlen($w) <= 3) {            // کلمه‌های کوتاه: فقط تطبیق دقیق (جلوگیری از «کسی»، «کسب»…)
                if (in_array($w, $tokens, true)) {
                    return $w;
                }
            } elseif (str_contains($n, $w) || str_contains($glued, $w)) {
                return $w;
            }
        }

        return null;
    }
}
