<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
    public function downloadSample(Request $request)
    {
        $filePath = base_path('public/assets/images/sample.xlsx');
        if (file_exists($filePath)) {
            return response()->download($filePath);
        } else {
            return asset('assets/images/sample.xlsx');
            abort(404); // File not found
        }
    }

    public function logout()
    {

        auth()->guard('web')->logout();
        return redirect()->route('login');
    }
}
