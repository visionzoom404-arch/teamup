<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Audit;
use App\Support\Jalali;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $r)
    {
        $q = User::latest('id');
        $r->filled('role') && $q->where('role', $r->role);
        $r->filled('q') && $q->where(fn ($w) => $w->where('name', 'like', "%{$r->q}%")->orWhere('phone', 'like', "%{$r->q}%")->orWhere('national_code', 'like', "%{$r->q}%"));
        $r->filled('id_status') && $q->where('id_status', $r->id_status);

        return view('admin.users', ['items' => $q->paginate(20)->withQueryString()]);
    }

    public function create() { return view('admin.user-form', ['u' => new User(['role' => 'guest'])]); }

    private function rules(?User $u = null): array
    {
        return [
            'name' => 'required|string|min:3|max:60',
            'phone' => ['required', 'regex:/^09\d{9}$/', Rule::unique('users', 'phone')->ignore($u?->id)],
            'email' => ['nullable', 'email', Rule::unique('users', 'email')->ignore($u?->id)],
            'role' => 'required|in:guest,host,admin',
            'password' => [$u ? 'nullable' : 'required', 'string', 'min:6'],
            'city' => 'nullable|string|max:60', 'bio' => 'nullable|string|max:600',
            'national_code' => 'nullable|digits:10', 'birth_date' => 'nullable|string|max:12',
            'card_number' => 'nullable|digits:16', 'sheba' => ['nullable', 'regex:/^IR\d{24}$/i'], 'bank_owner' => 'nullable|string|max:80', 'bank_name' => 'nullable|string|max:40',
            'commission_override' => 'nullable|integer|between:0,60',
            'id_status' => 'nullable|in:none,pending,verified,rejected', 'id_note' => 'nullable|string|max:200',
            'avatar' => 'nullable|image|max:3072',
        ];
    }

    private function payload(Request $r, ?User $u = null): array
    {
        $r->merge(['phone' => preg_replace('/\D/', '', Jalali::latinDigits((string) $r->phone))]);
        $d = $r->validate($this->rules($u));
        if (empty($d['password'])) {
            unset($d['password']);
        }
        $d['sheba'] = ! empty($d['sheba']) ? strtoupper($d['sheba']) : null;
        $d['commission_override'] = $d['commission_override'] ?? null;
        foreach (['email', 'city', 'bio', 'national_code', 'birth_date', 'card_number', 'bank_owner', 'bank_name', 'id_note'] as $k) {
            $d[$k] = $d[$k] ?? null;
        }
        $d['host_verified'] = $r->boolean('host_verified');
        $d['is_active'] = $r->boolean('is_active', true);
        $d['installment_unlocked'] = $r->boolean('installment_unlocked');
        if ($r->hasFile('avatar')) {
            $u?->avatar && Storage::disk('public')->delete($u->avatar);
            $d['avatar'] = $r->file('avatar')->store('avatars', 'public');
        }
        unset($d['id_status']);
        $d['id_status'] = $r->input('id_status', $u?->id_status ?? 'none');
        if ($d['role'] === 'host' && $d['national_code'] && $d['sheba'] && $d['card_number']) {
            $d['host_onboarded_at'] = $u?->host_onboarded_at ?? now();
        }

        return $d;
    }

    public function store(Request $r)
    {
        $u = User::create($this->payload($r));
        Audit::log('user.create', "#{$u->id} {$u->phone}");

        return redirect()->route('admin.users.edit', $u)->with('ok', 'کاربر ساخته شد.');
    }

    public function edit(User $user)
    {
        return view('admin.user-form', ['u' => $user, 'txs' => $user->transactions()->take(8)->get()]);
    }

    public function update(Request $r, User $user)
    {
        if ($user->id === auth()->id() && $r->input('role') !== 'admin') {
            return back()->withErrors(['role' => 'نقش خودتان را نمی‌توانید تغییر دهید.']);
        }
        $user->update($this->payload($r, $user));
        \Illuminate\Support\Facades\Cache::forget("mija.host.{$user->id}");
        Audit::log('user.update', "#{$user->id} {$user->phone}");

        return back()->with('ok', 'اطلاعات کاربر ذخیره شد.');
    }

    public function destroy(User $user)
    {
        abort_if($user->id === auth()->id(), 403, 'حساب خودتان را نمی‌توانید حذف کنید.');
        abort_if($user->isAdmin() && User::where('role', 'admin')->count() < 2, 403);
        $user->avatar && Storage::disk('public')->delete($user->avatar);
        Audit::log('user.delete', "#{$user->id} {$user->phone} {$user->name}");
        $user->delete();

        return redirect()->route('admin.users')->with('ok', 'کاربر و اطلاعات وابسته به او حذف شد.');
    }

    /** افزایش/کاهش دستی موجودی کیف پول با ثبت دلیل */
    public function wallet(Request $r, User $user)
    {
        $d = $r->validate(['amount' => 'required|integer|not_in:0', 'reason' => 'required|string|max:120']);
        $user->walletChange((int) $d['amount'], 'تنظیم توسط مدیر: '.$d['reason']);
        Audit::log('wallet.adjust', "#{$user->id}", $d);

        return back()->with('ok', 'موجودی کیف پول به‌روز شد.');
    }

    /** ورود به پنل کاربر (مهمان/میزبان) به‌جای خود او؛ با «بازگشت به حساب مدیر» برمی‌گردید */
    public function impersonate(User $user)
    {
        abort_if($user->isAdmin() || ! $user->is_active, 403, 'ورود به حساب این کاربر ممکن نیست.');
        $admin = auth()->user();
        Audit::log('impersonate.start', "#{$user->id} {$user->name}");
        session(['impersonator_id' => $admin->id]);
        Auth::login($user);

        return redirect($user->homeRoute())->with('ok', "اکنون در پنل «{$user->name}» هستید.");
    }

    public function leave(Request $r)
    {
        $id = $r->session()->pull('impersonator_id');
        abort_unless($id && ($admin = User::where('id', $id)->where('role', 'admin')->first()), 404);
        Auth::login($admin);
        $r->session()->regenerate();

        return redirect()->route('admin.dashboard')->with('ok', 'به حساب مدیریت خود بازگشتید.');
    }

    public function idPhoto(User $user)
    {
        abort_unless($user->id_photo && Storage::disk('local')->exists($user->id_photo), 404);

        return Storage::disk('local')->response($user->id_photo);
    }
}
