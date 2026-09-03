<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Libraries\PermissionsLibrary;

class PermissionsMiddleware
{
    protected $permissions;
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $module = null, $permission_type = null): mixed
    {
        if (!is_null($module) && !is_null($permission_type) && config('admin_permissions')) {
            $permissions = config('admin_permissions');
            $permissions = $permissions[$module][$permission_type];

            if (empty($permissions)) {
                return redirect()->route('access-denied');
            }
        }

        if (!config('admin_permissions')) {
            $this->permissions = new PermissionsLibrary();
            $this->permissions->initPermissions();

            // set permissions in config
            config()->set('admin_permissions', $this->permissions->getPermissions());

            // view()->share('admin_permissions', $this->permissions->getPermissions());
        }

        return $next($request);
    }
}
