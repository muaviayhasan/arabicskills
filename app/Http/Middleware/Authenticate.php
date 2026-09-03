<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        // Check if the current route is under the 'student' prefix and the guard is 'web'
        if ($request->routeIs('student.*')) {
            return $request->expectsJson() ? null : route('login');
        }

        // Check if the current route is under the 'admin' prefix and the guard is 'admin'
        if ($request->routeIs('admin.*')) {
            return $request->expectsJson() ? null : route('admin.login');
        }
    }
}
