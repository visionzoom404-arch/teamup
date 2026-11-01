<?php

use App\Http\Middleware\EnsureHostOnboarded;
use App\Http\Middleware\EnsureRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // پشت Cloudflare/nginx: تشخیص https و IP واقعی
        $middleware->trustProxies(at: '*');
        $middleware->validateCsrfTokens(except: ['pay/bitpay']);   // بیت‌پی با POST از سایت بانک برمی‌گردد
        $middleware->web(prepend: [\App\Http\Middleware\CanonicalHost::class]);
        $middleware->web(append: [\App\Http\Middleware\TrackAppClient::class]);
        $middleware->alias(['role' => EnsureRole::class, 'onboarded' => EnsureHostOnboarded::class]);
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => auth()->user()->homeRoute());
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // خطای ۵۰۰: مدیر سایت علتش را مستقیم می‌بیند (بقیه صفحه‌ی ساده‌ی فارسی)
        $exceptions->render(function (\Throwable $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')
                || $e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface
                || $e instanceof \Illuminate\Validation\ValidationException
                || $e instanceof \Illuminate\Auth\AuthenticationException
                || $e instanceof \Illuminate\Auth\Access\AuthorizationException
                || $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException
                || $e instanceof \Illuminate\Session\TokenMismatchException
                || $e instanceof \Illuminate\Http\Exceptions\HttpResponseException
                || config('app.debug')) {
                return null;
            }
            try {
                report($e);
                $admin = auth()->check() && auth()->user()->isAdmin();

                return response()->view($admin ? 'errors.admin500' : 'errors.500', ['e' => $e], 500);
            } catch (\Throwable) {
                return null;
            }
        });
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
