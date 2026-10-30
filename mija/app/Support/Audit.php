<?php

namespace App\Support;

use App\Models\ActivityLog;

/** ثبت اقدامات مدیر (گزارش فعالیت) */
class Audit
{
    public static function log(string $action, ?string $subject = null, array|string|null $meta = null): void
    {
        ActivityLog::create([
            'user_id' => auth()->id(), 'action' => $action, 'subject' => $subject,
            'meta' => is_array($meta) ? json_encode($meta, JSON_UNESCAPED_UNICODE) : $meta, 'ip' => request()?->ip(),
        ]);
    }
}
