<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketingContact extends Model
{
    protected $guarded = [];

    protected $casts = ['last_sent_at' => 'datetime', 'registered_at' => 'datetime'];

    public const STATUS = ['new' => ['ارسال‌نشده', 'gray'], 'sent' => ['پیامک رفته', 'blue'], 'registered' => ['ثبت‌نام کرد', 'green'], 'optout' => ['لغو کرده', 'red'], 'failed' => ['ناموفق', 'gold']];

    /** شماره‌ی ایرانی را به قالب 09xxxxxxxxx درمی‌آورد؛ نامعتبر ← null */
    public static function normalize(string $raw): ?string
    {
        $d = preg_replace('/\D/', '', \App\Support\Jalali::latinDigits($raw));
        $d = preg_replace('/^(0098|098|98|0)/', '', $d);

        return preg_match('/^9\d{9}$/', $d) ? '0'.$d : null;
    }

    /** پیام: {name} و {link} جایگزین می‌شود؛ «لغو۱۱» همیشه در پایان می‌آید */
    public function render(string $tpl, ?string $link): string
    {
        $text = str_replace(['{name}', '{link}'], [$this->name ?: 'دوست عزیز', $link ?: ''], $tpl);
        $text = trim(preg_replace('/[ \t]+\n/', "\n", preg_replace('/ {2,}/', ' ', $text)));
        if (! preg_match('/لغو\s*[۱1]{2}|لغو\s*۱۱/u', $text)) {
            $text .= "\nلغو۱۱";
        }

        return $text;
    }

    public const DEFAULT_TEMPLATE = "سلام {name}\nمی‌جا؛ پلتفرم رزرو ویلا و کلبه. صاحب ویلا یا کلبه هستید؟ رایگان ثبت کنید و مهمان بگیرید:\n{link}\nلغو۱۱";
}
