<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Payout;
use App\Models\Property;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /** پروفایل خصوصی — بسته به نقش (مهمان / میزبان / مدیر) محتوای متفاوت */
    public function edit()
    {
        $u = auth()->user();
        $data = ['u' => $u, 'invite' => url('/register?ref='.$u->referral_code), 'invited' => User::where('referred_by', $u->id)->count()];

        if ($u->isGuest()) {
            $data += [
                'lv' => $u->level(),
                'stats' => [
                    'trips' => $u->bookings()->whereIn('status', ['confirmed', 'completed'])->count(),
                    'reviews' => \App\Models\Review::where('guest_id', $u->id)->count(),
                    'favorites' => $u->favorites()->count(),
                    'invited' => User::where('referred_by', $u->id)->count(),
                ],
            ];
        } elseif ($u->isHost()) {
            $check = $u->hostChecklist();
            $data += [
                'hs' => $u->hostStats(),
                'check' => $check,
                'percent' => (int) round(collect($check)->where(1, true)->count() / count($check) * 100),
            ];
        } else {
            $data += [
                'adm' => [
                    'users' => User::count(), 'properties' => Property::count(), 'bookings' => Booking::count(),
                    'pending_props' => Property::where('status', 'pending')->count(), 'pending_payouts' => Payout::where('status', 'pending')->count(),
                    'admins' => User::where('role', 'admin')->get(),
                ],
            ];
        }

        return view('profile', $data);
    }

    public function update(Request $r)
    {
        $u = $r->user();
        $rules = [
            'name' => 'required|string|min:3|max:60',
            'email' => 'nullable|email|unique:users,email,'.$u->id,
            'city' => 'nullable|string|max:60', 'bio' => 'nullable|string|max:600',
            'avatar' => 'nullable|image|max:3072',
        ];
        if ($u->isHost()) {
            $rules += ['national_id' => 'nullable|digits:10', 'sheba' => ['nullable', 'regex:/^IR\d{24}$/i'], 'bank_owner' => 'nullable|string|max:80'];
        }
        $d = $r->validate($rules);
        if ($r->hasFile('avatar')) {
            $u->avatar && Storage::disk('public')->delete($u->avatar);
            $d['avatar'] = $r->file('avatar')->store('avatars', 'public');
        }
        if (isset($d['sheba'])) {
            $d['sheba'] = strtoupper($d['sheba']);
        }
        $u->update($d);
        Cache::forget("mija.host.{$u->id}");

        return back()->with('ok', 'پروفایل ذخیره شد.');
    }

    public function password(Request $r)
    {
        $d = $r->validate(['current' => 'required|current_password', 'password' => 'required|string|min:6|confirmed']);
        $r->user()->update(['password' => $d['password']]);

        return back()->with('ok', 'رمز عبور تغییر کرد.');
    }

    /** صفحه‌ی عمومی میزبان */
    public function publicHost(User $user)
    {
        abort_unless($user->isHost() && $user->is_active, 404);
        $props = $user->properties()->approved()->with('images')->latest('id')->get();
        $reviews = \App\Models\Review::with(['guest', 'property'])->published()->whereIn('property_id', $props->pluck('id'))->latest('id')->take(8)->get();

        return view('host-public', ['h' => $user, 'hs' => $user->hostStats(), 'props' => $props, 'reviews' => $reviews]);
    }

    /** صفحه‌ی عمومی مهمان: اقامتگاه‌های رزروکرده، نظرها و عکس‌های منتشرشده */
    public function publicGuest(User $user)
    {
        abort_unless($user->isGuest() && $user->is_active, 404);
        $reviews = \App\Models\Review::with('property')->published()->where('guest_id', $user->id)->latest('id')->get();
        $trips = $user->bookings()->with('property.images')->where('status', 'completed')->latest('check_out')->get()->unique('property_id');

        return view('guest-public', [
            'g' => $user, 'lv' => $user->level(), 'reviews' => $reviews, 'trips' => $trips,
            'photos' => $reviews->flatMap(fn ($r) => $r->photoUrls())->take(12),
        ]);
    }
}
