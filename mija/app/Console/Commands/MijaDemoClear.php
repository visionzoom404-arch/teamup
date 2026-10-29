<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MijaDemoClear extends Command
{
    protected $signature = 'mija:demo-clear {--force : بدون پرسش}';

    protected $description = 'حذف حساب‌ها و اقامتگاه‌های نمونه (برای آماده‌سازی انتشار واقعی)';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('همه‌ی کاربران و اقامتگاه‌های نمونه (و رزروهایشان) حذف شوند؟')) {
            return self::SUCCESS;
        }
        $users = User::whereIn('phone', ['09120000000', '09121111111', '09122222222', '09123333333', '09124444444'])->where('role', '!=', 'admin')->get();
        foreach ($users as $u) {
            Storage::disk('public')->deleteDirectory('properties');
            $u->delete();
        }
        Storage::disk('public')->deleteDirectory('seed');
        $this->info($users->count().' حساب نمونه حذف شد.');

        return self::SUCCESS;
    }
}
