<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\BannedWord;
use App\Models\Booking;
use App\Models\Page;
use App\Models\Payment;
use App\Models\Property;
use App\Models\SmsLog;
use App\Models\User;
use App\Support\Audit;
use App\Support\DefaultPages;
use App\Support\Profanity;
use App\Support\Sms;
use Illuminate\Http\Request;

/** محتوا و ابزارهای مدیر: صفحه‌ها، کلمات ممنوع، پیامک، گزارش فعالیت، ارسال همگانی، خروجی */
class ContentController extends Controller
{
    /* ---------- صفحه‌ها و متن‌ها ---------- */
    public function pages()
    {
        return view('admin.pages', ['saved' => Page::pluck('updated_at', 'slug'), 'slugs' => DefaultPages::SLUGS]);
    }

    public function pageEdit(string $slug)
    {
        $page = DefaultPages::find($slug);
        abort_unless($page, 404);

        return view('admin.page-edit', ['page' => $page, 'custom' => Page::where('slug', $slug)->exists()]);
    }

    public function pageUpdate(Request $r, string $slug)
    {
        abort_unless(isset(DefaultPages::SLUGS[$slug]) || Page::where('slug', $slug)->exists(), 404);
        $d = $r->validate(['title' => 'required|string|max:120', 'body' => 'required|string|max:60000']);
        Page::updateOrCreate(['slug' => $slug], $d);
        Audit::log('page.update', $slug);

        return back()->with('ok', 'صفحه ذخیره شد.');
    }

    public function pageDestroy(string $slug)
    {
        Page::where('slug', $slug)->delete();
        Audit::log('page.reset', $slug);

        return redirect()->route('admin.pages')->with('ok', 'متن این صفحه به حالت پیش‌فرض برگشت.');
    }

    /* ---------- کلمات ممنوع ---------- */
    public function words()
    {
        return view('admin.words', ['items' => BannedWord::orderBy('word')->get(), 'defaults' => Profanity::DEFAULTS]);
    }

    public function wordStore(Request $r)
    {
        $d = $r->validate(['words' => 'required|string|max:2000']);
        $n = 0;
        foreach (preg_split('/[\n,،]+/u', $d['words']) as $w) {
            $w = trim($w);
            if (mb_strlen($w) >= 2 && ! BannedWord::where('word', $w)->exists()) {
                BannedWord::create(['word' => $w]);
                $n++;
            }
        }
        Profanity::forget();
        Audit::log('words.add', (string) $n);

        return back()->with('ok', fa($n).' کلمه اضافه شد؛ از این پس در نظرات و پیام‌ها مجاز نیست.');
    }

    public function wordDestroy(BannedWord $word)
    {
        $word->delete();
        Profanity::forget();

        return back();
    }

    /* ---------- پیامک و گزارش فعالیت ---------- */
    public function sms()
    {
        return view('admin.sms', ['items' => SmsLog::latest('id')->paginate(30), 'stats' => SmsLog::selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status')]);
    }

    public function activity()
    {
        return view('admin.activity', ['items' => ActivityLog::with('user')->latest('id')->paginate(40)]);
    }

    /* ---------- ارسال همگانی ---------- */
    public function broadcast()
    {
        return view('admin.broadcast', ['counts' => ['guest' => User::where('role', 'guest')->count(), 'host' => User::where('role', 'host')->count()]]);
    }

    public function broadcastSend(Request $r)
    {
        $d = $r->validate(['to' => 'required|in:guest,host,all', 'title' => 'required|string|max:80', 'body' => 'nullable|string|max:300', 'url' => 'nullable|url', 'sms' => 'nullable|boolean']);
        $q = User::where('is_active', true)->where('role', '!=', 'admin');
        $d['to'] !== 'all' && $q->where('role', $d['to']);
        $n = 0;
        $q->chunkById(200, function ($users) use ($d, $r, &$n) {
            foreach ($users as $u) {
                $u->notify($d['title'], $d['body'] ?? null, $d['url'] ?? null, $r->boolean('sms'));
                $n++;
            }
        });
        Audit::log('broadcast', $d['to'], ['n' => $n, 'title' => $d['title']]);

        return back()->with('ok', 'برای '.fa($n).' نفر ارسال شد.');
    }

    /* ---------- جستجوی سراسری ---------- */
    public function search(Request $r)
    {
        $q = trim((string) $r->q);
        $res = ['users' => collect(), 'bookings' => collect(), 'properties' => collect()];
        if ($q !== '') {
            $res = [
                'users' => User::where('name', 'like', "%$q%")->orWhere('phone', 'like', "%$q%")->orWhere('national_code', 'like', "%$q%")->take(10)->get(),
                'bookings' => Booking::with(['property', 'guest'])->where('code', 'like', '%'.strtoupper($q).'%')->take(10)->get(),
                'properties' => Property::where('title', 'like', "%$q%")->orWhere('city', 'like', "%$q%")->take(10)->get(),
            ];
        }

        return view('admin.search', ['q' => $q] + $res);
    }

    /* ---------- خروجی CSV و نسخه‌ی پشتیبان ---------- */
    public function export(string $what)
    {
        abort_unless(in_array($what, ['users', 'bookings', 'payments']), 404);
        $rows = match ($what) {
            'users' => User::all()->map(fn ($u) => [$u->id, $u->name, $u->phone, $u->role, $u->city, $u->wallet, $u->created_at])->prepend(['id', 'name', 'phone', 'role', 'city', 'wallet', 'created_at']),
            'bookings' => Booking::all()->map(fn ($b) => [$b->code, $b->property_id, $b->guest_id, $b->check_in->toDateString(), $b->check_out->toDateString(), $b->nights, $b->total, $b->commission, $b->host_payout, $b->status])->prepend(['code', 'property', 'guest', 'in', 'out', 'nights', 'total', 'commission', 'host_payout', 'status']),
            'payments' => Payment::all()->map(fn ($p) => [$p->id, $p->user_id, $p->booking_id, $p->amount, $p->gateway, $p->status, $p->ref_id, $p->created_at])->prepend(['id', 'user', 'booking', 'amount', 'gateway', 'status', 'ref', 'created_at']),
        };
        Audit::log('export', $what);

        return response()->streamDownload(function () use ($rows) {
            $f = fopen('php://output', 'w');
            fwrite($f, "\xEF\xBB\xBF");
            foreach ($rows as $row) {
                fputcsv($f, $row instanceof \Illuminate\Support\Collection ? $row->all() : $row);
            }
            fclose($f);
        }, "mija-$what-".now()->format('Ymd').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function backup()
    {
        $path = config('database.connections.sqlite.database');
        abort_unless(config('database.default') === 'sqlite' && is_file($path), 404, 'پشتیبان‌گیری فقط برای دیتابیس SQLite فعال است.');
        Audit::log('backup');

        return response()->download($path, 'mija-backup-'.now()->format('Ymd-His').'.sqlite');
    }

    /* ---------- صندوق پنهان اقساط (فقط مدیر) ---------- */
    public function pool()
    {
        return view('admin.pool', [
            'items' => User::where('pool_amount', '>', 0)->orderByDesc('pool_amount')->paginate(30),
            'total' => (int) User::sum('pool_amount'), 'unlocked' => User::where('installment_unlocked', true)->count(),
        ]);
    }
}
