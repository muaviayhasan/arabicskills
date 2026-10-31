<?php

use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Route::redirect, not a closure, so the route table can be cached.
Route::redirect('/', '/login');

// Clearing the caches was a public URL that anyone could hit, as often as they
// liked. It is an admin action, and it rebuilds afterwards instead of leaving
// the site to parse config and compile the route table on every request.
Route::middleware('auth:admin')
    ->get('/clear-cache', [AdminController::class, 'refreshCaches'])
    ->name('admin.refresh-caches');

// Removed: /install and /migrate, both unauthenticated.
//
// /install ran `migrate:fresh --seed` unless env('APP_ENV') was 'production'.
// Caching the config stops .env being read, so env() returns null, the guard
// inverts, and that URL drops every table and reseeds. /migrate ran pending
// migrations on request. Both belong on the command line, over ssh.
