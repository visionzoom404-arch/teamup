<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** ثبت اقامتگاه فقط پس از تکمیل اطلاعات هویتی و بانکی میزبان */
class EnsureHostOnboarded
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->user()?->isOnboarded()) {
            return redirect()->route('host.onboard')->with('warn', 'برای ثبت اقامتگاه، ابتدا اطلاعات هویتی و حساب بانکی خود را تکمیل کنید.');
        }

        return $next($request);
    }
}
