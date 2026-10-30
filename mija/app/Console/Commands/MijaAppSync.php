<?php

namespace App\Console\Commands;

use App\Models\AppRelease;
use Illuminate\Console\Command;

class MijaAppSync extends Command
{
    protected $signature = 'mija:app-sync';

    protected $description = 'ثبت نسخه‌ی اپلیکیشن اندروید همراه بسته (storage/app/apk) در دیتابیس';

    public function handle(): int
    {
        $n = AppRelease::sync();
        $l = AppRelease::latest();
        $this->info($n ? 'نسخه‌ی جدید اپ ثبت شد.' : 'تغییری نبود.'.($l ? " آخرین نسخه: {$l->version_name} ({$l->version_code})" : ' (هنوز فایل اپ ندارید)'));

        return self::SUCCESS;
    }
}
