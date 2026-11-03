<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MarketingContact as C;
use App\Models\Setting;
use App\Models\ShortLink;
use App\Support\Audit;
use App\Support\Sms;
use Illuminate\Http\Request;

/** بازاریابی غیرمستقیم: لیست شماره‌ها + پیامک دعوت به ثبت‌نام میزبان (دستی یا گروهی) */
class MarketingController extends Controller
{
    private function template(): string
    {
        return (string) (setting('mk_template') ?: C::DEFAULT_TEMPLATE);
    }

    private function link(C $c): ?string
    {
        try {
            return ShortLink::make(url('/register').'?as=host&utm_source=sms');
        } catch (\Throwable) {
            return url('/register').'?as=host';
        }
    }

    private function dailyCap(): int
    {
        return max(1, (int) (setting('mk_daily_cap') ?: 200));
    }

    private function sentToday(): int
    {
        return C::whereDate('last_sent_at', today())->count();
    }

    /** ارسال به یک مخاطب؛ [موفق؟، پیام خطا] */
    private function sendOne(C $c, string $tpl): array
    {
        if ($c->status === 'optout') {
            return [false, 'این شماره لغو کرده است'];
        }
        $text = $c->render($tpl, $this->link($c));
        $ok = Sms::raw($c->phone, $text, 0, 'marketing');
        $c->forceFill([
            'status' => $ok ? ($c->status === 'registered' ? 'registered' : 'sent') : 'failed', 'last_sent_at' => now(), 'sent_count' => $c->sent_count + 1,
            'last_error' => $ok ? null : mb_substr((string) Sms::$lastError, 0, 190),
        ])->save();

        return [$ok, Sms::$lastError];
    }

    public function index(Request $r)
    {
        $st = $r->query('status');
        $q = C::query()->latest('id');
        if ($st && isset(C::STATUS[$st])) {
            $q->where('status', $st);
        }
        if ($s = trim((string) $r->query('q'))) {
            $q->where(fn ($x) => $x->where('phone', 'like', "%$s%")->orWhere('name', 'like', "%$s%"));
        }

        return view('admin.marketing', [
            'items' => $q->paginate(20)->withQueryString(), 'counts' => C::selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status'),
            'tpl' => $this->template(), 'cap' => $this->dailyCap(), 'today' => $this->sentToday(), 'st' => $st,
            'preview' => (new C(['name' => 'علی']))->render($this->template(), 'mija.click/s/abc123'),
            'smsReady' => Sms::enabled(),
        ]);
    }

    public function template_save(Request $r)
    {
        $d = $r->validate(['mk_template' => 'required|string|min:10|max:400', 'mk_daily_cap' => 'nullable|integer|min:1|max:5000']);
        Setting::put('mk_template', $d['mk_template']);
        Setting::put('mk_daily_cap', $d['mk_daily_cap'] ?? 200);
        Audit::log('marketing.template');

        return back()->with('ok', 'متن پیام و سقف روزانه ذخیره شد.');
    }

    /** افزودن شماره‌ها (هر خط: شماره و اختیاری نام) و در صورت انتخاب، ارسال فوری */
    public function store(Request $r)
    {
        $d = $r->validate(['numbers' => 'required|string|max:20000', 'text' => 'nullable|string|max:400', 'send' => 'nullable|boolean']);
        $tpl = trim((string) ($d['text'] ?? '')) ?: $this->template();
        $added = $dup = $bad = $sent = $fail = 0;
        $errors = [];
        foreach (preg_split('/\R+/', $d['numbers']) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            if (! preg_match('/[\d۰-۹٠-٩\+]{9,15}/u', $line, $m)) {
                $bad++;

                continue;
            }
            $phone = C::normalize($m[0]);
            if (! $phone) {
                $bad++;

                continue;
            }
            $name = trim(preg_replace('/[\d۰-۹\+,;،\-\|]+/u', ' ', str_replace($m[0], ' ', $line)));
            $c = C::firstOrNew(['phone' => $phone]);
            if ($c->exists) {
                $dup++;
            } else {
                $c->fill(['name' => mb_substr($name, 0, 60) ?: null, 'status' => \App\Models\User::where('phone', $phone)->exists() ? 'registered' : 'new'])->save();
                $added++;
            }
            if (! empty($d['send']) && ! in_array($c->status, ['optout', 'registered'], true)) {
                if ($this->sentToday() >= $this->dailyCap()) {
                    $errors[] = 'سقف ارسال امروز پر شد؛ بقیه فقط ذخیره شدند.';
                    continue;
                }
                [$ok, $err] = $this->sendOne($c, $tpl);
                $ok ? $sent++ : $fail++;
                if (! $ok && $err) {
                    $errors[] = $err;
                }
            }
        }
        Audit::log('marketing.store', null, compact('added', 'dup', 'bad', 'sent', 'fail'));
        $msg = "افزوده: {$added} · تکراری: {$dup} · نامعتبر: {$bad}".(! empty($d['send']) ? " · ارسال موفق: {$sent} · ناموفق: {$fail}" : '');
        $back = back()->with($fail && ! $sent ? 'warn' : 'ok', $msg);
        if ($errors) {
            $back->withErrors(['send' => implode(' | ', array_slice(array_unique($errors), 0, 2))]);
        }

        return $back;
    }

    public function send(C $contact)
    {
        if ($this->sentToday() >= $this->dailyCap()) {
            return back()->withErrors(['send' => 'سقف ارسال امروز پر شده است.']);
        }
        [$ok, $err] = $this->sendOne($contact, $this->template());

        return $ok ? back()->with('ok', 'پیامک ارسال شد.') : back()->withErrors(['send' => $err ?: 'ارسال نشد']);
    }

    /** ارسال گروهی به ارسال‌نشده‌ها (حداکثر ۵۰ شماره در هر بار و در سقف روزانه) */
    public function sendBulk()
    {
        $room = max(0, $this->dailyCap() - $this->sentToday());
        $take = min(50, $room);
        if ($take === 0) {
            return back()->withErrors(['send' => 'سقف ارسال امروز پر شده است.']);
        }
        $ok = $fail = 0;
        $last = null;
        foreach (C::where('status', 'new')->orderBy('id')->limit($take)->get() as $c) {
            [$o, $e] = $this->sendOne($c, $this->template());
            $o ? $ok++ : $fail++;
            $last = $e ?: $last;
            if (! $o && in_array(Sms::$lastKind, ['auth', 'credit'], true)) {
                break;   // مشکل پنل است؛ ادامه ندهیم
            }
        }

        return back()->with($ok ? 'ok' : 'warn', "ارسال گروهی: موفق {$ok} · ناموفق {$fail}")->withErrors($fail ? ['send' => (string) $last] : []);
    }

    public function optout(C $contact)
    {
        $contact->update(['status' => $contact->status === 'optout' ? 'new' : 'optout']);

        return back()->with('ok', $contact->status === 'optout' ? 'شماره در فهرست «لغو کرده» قرار گرفت و دیگر پیامک نمی‌گیرد.' : 'از فهرست لغو خارج شد.');
    }

    public function destroy(C $contact)
    {
        $contact->delete();

        return back()->with('ok', 'حذف شد.');
    }
}
