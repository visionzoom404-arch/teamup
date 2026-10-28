<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Jalali;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    private function normalizePhone(?string $p): string
    {
        $p = preg_replace('/\D/', '', Jalali::latinDigits((string) $p));
        if (str_starts_with($p, '98')) {
            $p = '0'.substr($p, 2);
        }
        if (strlen($p) === 10 && str_starts_with($p, '9')) {
            $p = '0'.$p;
        }

        return $p;
    }

    public function showLogin() { return view('auth.login'); }
    public function showRegister(Request $r) { return view('auth.register', ['role' => $r->query('as', 'guest')]); }

    public function login(Request $r)
    {
        $r->merge(['phone' => $this->normalizePhone($r->phone)]);
        $data = $r->validate(['phone' => ['required', 'regex:/^09\d{9}$/'], 'password' => 'required|string']);

        if (! Auth::attempt($data, $r->boolean('remember'))) {
            return back()->withInput($r->only('phone'))->withErrors(['phone' => 'شماره موبایل یا رمز عبور اشتباه است.']);
        }
        if (! Auth::user()->is_active) {
            Auth::logout();

            return back()->withErrors(['phone' => 'حساب کاربری شما مسدود شده است.']);
        }
        $r->session()->regenerate();

        return redirect()->intended(Auth::user()->homeRoute());
    }

    public function register(Request $r)
    {
        $r->merge(['phone' => $this->normalizePhone($r->phone)]);
        $data = $r->validate([
            'name' => 'required|string|min:3|max:60',
            'phone' => ['required', 'regex:/^09\d{9}$/', 'unique:users,phone'],
            'password' => 'required|string|min:6|confirmed',
            'role' => 'required|in:guest,host',
        ]);
        $user = User::create($data);
        Auth::login($user);
        $user->notify('به می‌جا خوش آمدید 👋', $user->isHost() ? 'اولین اقامتگاه خود را ثبت کنید.' : 'اقامتگاه رویایی‌تان را پیدا کنید.');

        return redirect($user->isHost() ? route('host.properties.create') : $user->homeRoute());
    }

    public function logout(Request $r)
    {
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();

        return redirect('/');
    }
}
