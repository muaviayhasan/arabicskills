<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Section;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
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
            if ($request->type == "listening") {
                $activities = $activities->map(function ($activity) {
                    return [
                        'id' => $activity->id,
                        'activity' => read_image($activity->activity),
                    ];
                });
            }

            return response()->json($activities, 200);
        } catch (Exception $e) {

            return response()->json(["errors" => $e->getMessage()], 500);
        }
    }
}
