<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Opcodes\LogViewer\Facades\LogViewer;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // The log viewer locks nobody out until it is told how to decide. Its
        // own auth() denies only when an auth callback or a viewLogViewer gate
        // exists; with neither, every branch falls through and the viewer and
        // its API answer anyone. /sf-log-viewer was world readable, and the
        // logs carry stack traces, queries, server paths and student details.
        //
        // The admin guard decides it, not the web guard the viewer's own
        // middleware resolves: an admin is signed in on 'admin', so a gate
        // reading the default guard would lock them out along with everyone.
        LogViewer::auth(fn () => auth()->guard('admin')->check());
    }
}
