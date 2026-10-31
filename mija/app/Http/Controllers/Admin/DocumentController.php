<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/** بررسی مدارک هویتی میزبانان: کارت ملی، عکس همراه کارت، مدرک مالکیت + اطلاعات بانکی */
class DocumentController extends Controller
{
    public const TYPES = ['id' => 'id_photo', 'selfie' => 'selfie_photo', 'ownership' => 'ownership_doc'];

    public function index(Request $r)
    {
        $tab = $r->query('tab', 'pending');
        $q = User::where('role', 'host')->orderByDesc('docs_submitted_at')->orderByDesc('id');
        match ($tab) {
            'verified' => $q->where('id_status', 'verified'),
            'rejected' => $q->where('id_status', 'rejected'),
            'none' => $q->whereIn('id_status', ['none', ''])->orWhereNull('id_status'),
            'all' => null,
            default => $q->where('id_status', 'pending'),
        };
        $counts = User::where('role', 'host')->selectRaw('id_status, count(*) c')->groupBy('id_status')->pluck('c', 'id_status');

        return view('admin.documents', ['items' => $q->paginate(12)->withQueryString(), 'tab' => $tab, 'counts' => $counts, 'total' => User::where('role', 'host')->count()]);
    }

    /** نمایش فایل مدرک (فقط مدیر) */
    public function file(User $user, string $type)
    {
        $col = self::TYPES[$type] ?? null;
        abort_unless($col && $user->$col && Storage::disk('local')->exists($user->$col), 404);

        return Storage::disk('local')->response($user->$col, null, ['Cache-Control' => 'private, no-store']);
    }

    public function decide(Request $r, User $user)
    {
        abort_unless($user->isHost(), 404);
        $d = $r->validate(['action' => 'required|in:approve,reject', 'note' => 'nullable|string|max:200']);
        if ($d['action'] === 'reject') {
            if (trim((string) ($d['note'] ?? '')) === '') {
                return back()->withErrors(['note' => 'برای رد مدارک، دلیل را بنویسید تا میزبان بداند چه چیزی را اصلاح کند.']);
            }
            $user->update(['id_status' => 'rejected', 'id_note' => $d['note'], 'host_verified' => false]);
            Audit::log('docs.reject', "#{$user->id}", ['note' => $d['note']]);
            $user->notify('مدارک شما تأیید نشد', $d['note'].' — لطفاً مدارک را اصلاح و دوباره ارسال کنید.', route('host.onboard', ['step' => 1]), true);

            return back()->with('ok', 'مدارک رد شد و دلیل برای میزبان (اعلان + پیامک) ارسال گردید.');
        }
        $user->update(['id_status' => 'verified', 'id_note' => null, 'host_verified' => true]);
        Audit::log('docs.approve', "#{$user->id}");
        $user->notify('هویت شما تأیید شد', 'اکنون می‌توانید اقامتگاه ثبت و منتشر کنید.', route('host.properties.create'), true);

        return back()->with('ok', 'مدارک تأیید شد و میزبان خبردار شد.');
    }
}
