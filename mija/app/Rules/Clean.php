<?php

namespace App\Rules;

use App\Support\Profanity;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** متن نباید شامل کلمات رکیک/ممنوع باشد */
class Clean implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && ($w = Profanity::find($value))) {
            $fail("استفاده از کلمه‌ی «{$w}» مجاز نیست؛ لطفاً متن را محترمانه بنویسید.");
        }
    }
}
