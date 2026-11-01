<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Section;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    /**
     * Clear the caches, and on production build them again.
     *
     * Clearing alone leaves production parsing every config file and compiling
     * the whole route table on each request, which is slower than before it
     * was cleared, so there it rebuilds what it just dropped.
     *
     * Nowhere else, though. Building a config cache freezes whichever
     * environment did the building, and the test suite exercises this route:
     * it baked APP_ENV=testing and SESSION_DRIVER=array into
     * bootstrap/cache/config.php and left it there, so the running app got the
     * array session driver, nothing persisted, the CSRF token could never
     * match the session, and every Livewire request came back as "This page
     * has expired". A developer who ran the tests then opened the site could
     * not sign in at all.
     */
    public function refreshCaches()
    {
        Artisan::call('optimize:clear');

        if (app()->isProduction()) {
            foreach (['config:cache', 'route:cache', 'view:cache'] as $command) {
                Artisan::call($command);
            }
        }

        return redirect()->route('admin.dashboard');
    }

    public function adminLogout()
    {
        Auth::guard('admin')->logout();

        return redirect()->route('admin.login');
    }

    public function fetchActivities(Request $request)
    {
        try {

            $sec = Section::find($request->section_id);

            $activities = Activity::where('type', $request->type)
                ->when($sec, function ($query) use ($sec) {
                    $query->whereRelation('Grade', 'id', '=', $sec->grade_id);
                })
                ->get();
            if ($request->type == 'listening') {
                $activities = $activities->map(function ($activity) {
                    return [
                        'id' => $activity->id,
                        'activity' => read_image($activity->activity),
                    ];
                });
            }

            return response()->json($activities, 200);
        } catch (Exception $e) {

            return response()->json(['errors' => $e->getMessage()], 500);
        }
    }
}
