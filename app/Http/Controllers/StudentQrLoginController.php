<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Stevebauman\Location\Facades\Location;

class StudentQrLoginController extends Controller
{
    public function __invoke(Request $request, Student $student)
    {
        Auth::loginUsingId($student->id);

        $ip = $request->ip();
        $location = Location::get($ip);

        $student->logs()->create([
            'ip' => $ip,
            'location' => $location
                ? 'City : ' . ($location->cityName ?? 'N/A') .
                    ', Region : ' . ($location->regionName ?? 'N/A') .
                    ', Country : ' . ($location->countryName ?? 'N/A')
                : 'Unknown',
            'path' => '/qr-login',
            'username' => $student->user_name,
        ]);

        return redirect()->route('student.dashboard');
    }
}
