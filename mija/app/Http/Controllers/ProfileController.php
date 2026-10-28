<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function edit() { return view('profile', ['u' => auth()->user()]); }

    public function update(Request $r)
    {
        $u = $r->user();
        $d = $r->validate([
            'name' => 'required|string|min:3|max:60',
            'email' => 'nullable|email|unique:users,email,'.$u->id,
            'city' => 'nullable|string|max:60', 'bio' => 'nullable|string|max:600',
            'national_id' => 'nullable|digits:10',
            'sheba' => ['nullable', 'regex:/^IR\d{24}$/i'], 'bank_owner' => 'nullable|string|max:80',
            'avatar' => 'nullable|image|max:3072',
        ]);
        if ($r->hasFile('avatar')) {
            $u->avatar && Storage::disk('public')->delete($u->avatar);
            $d['avatar'] = $r->file('avatar')->store('avatars', 'public');
        }
        $d['sheba'] = isset($d['sheba']) ? strtoupper($d['sheba']) : null;
        $u->update($d);

        return back()->with('ok', 'پروفایل ذخیره شد.');
    }

    public function password(Request $r)
    {
        $d = $r->validate(['current' => 'required|current_password', 'password' => 'required|string|min:6|confirmed']);
        $r->user()->update(['password' => $d['password']]);

        return back()->with('ok', 'رمز عبور تغییر کرد.');
    }
}
