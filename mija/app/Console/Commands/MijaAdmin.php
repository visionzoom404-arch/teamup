<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\Jalali;
use Illuminate\Console\Command;

class MijaAdmin extends Command
{
    protected $signature = 'mija:admin {phone : شماره موبایل مدیر} {password : رمز عبور} {--name=مدیر می‌جا}';

    protected $description = 'ساخت یا بازنشانی حساب مدیر سایت';

    public function handle(): int
    {
        $phone = preg_replace('/\D/', '', Jalali::latinDigits($this->argument('phone')));
        $password = Jalali::latinDigits($this->argument('password'));
        if (! preg_match('/^09\d{9}$/', $phone)) {
            $this->error('شماره موبایل باید مثل 09123456789 باشد.');

            return self::FAILURE;
        }
        if (strlen($password) < 6) {
            $this->error('رمز عبور باید حداقل ۶ کاراکتر باشد.');

            return self::FAILURE;
        }
        User::updateOrCreate(['phone' => $phone], ['name' => $this->option('name'), 'password' => $password, 'role' => 'admin', 'is_active' => true]);
        $this->info("مدیر سایت آماده است: $phone");

        return self::SUCCESS;
    }
}
