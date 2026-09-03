<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        // If the route is for students and the web guard is authenticated
        if ($request->routeIs('login') && Auth::guard('web')->check()) {
            return redirect(RouteServiceProvider::HOME);
        }

        // If the route is for admin and the admin guard is authenticated
        if ($request->routeIs('admin.login') && Auth::guard('admin')->check()) {
            return redirect(RouteServiceProvider::ADMIN_HOME);
        }

        return $next($request);
    }
}
