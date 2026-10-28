<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $user = $request->user();
        abort_unless($user && in_array($user->role, $roles, true), 403, 'دسترسی به این بخش برای شما مجاز نیست.');
        abort_unless($user->is_active, 403, 'حساب کاربری شما مسدود شده است.');

        return $next($request);
    }
}
