<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** www.mija.click ← 301 → mija.click (یک آدرس واحد برای سئو) */
class CanonicalHost
{
    public function handle(Request $request, Closure $next)
    {
        $host = $request->getHost();
        $canon = parse_url((string) config('app.url'), PHP_URL_HOST);
        if ($canon && ! app()->runningUnitTests() && str_starts_with($host, 'www.') && substr($host, 4) === $canon) {
            return redirect()->to(rtrim(config('app.url'), '/').$request->getRequestUri(), 301);
        }

        return $next($request);
    }
}
