<?php

namespace App\Livewire\Student\Auth;

use App\Models\Student;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Stevebauman\Location\Facades\Location;

class StudentLogin extends Component
{
    public $showPass = true;
    public $username;
    public $password;

    private function astUsernameVariants(string $username): array
    {
        $username = strtoupper(trim($username));

        $variants = [$username];

        if (preg_match('/^AST(\d{1,})$/', $username, $matches)) {
            $digits = $matches[1];

            // Padded form (AST + 6 digits)
            if (strlen($digits) <= 6) {
                $variants[] = 'AST' . str_pad($digits, 6, '0', STR_PAD_LEFT);
            }

            // Unpadded form (AST + number without leading zeros)
            // e.g. AST000205 -> AST205
            $variants[] = 'AST' . ((string) intval($digits));
        }

        return array_values(array_unique($variants));
    }

    public function login()
    {
        $this->validate([
            'username' => 'required',
            'password' => "required",
        ]);

        $username = trim((string) $this->username);

        $candidates = array_values(array_unique(array_merge(
            [$username, strtoupper($username)],
            $this->astUsernameVariants($username)
        )));

        $std = Student::whereIn('user_name', $candidates)
            ->where('password', $this->password)
            ->first();

            
        if ($std)
        {
            Auth::loginUsingId($std->id);
            $ip       = request()->ip();
            $location = Location::get($ip);

            $std->logs()->create([
                'ip'       => $ip,
                'location' => $location
                    ? 'City : ' . ($location->cityName ?? 'N/A') .
                    ', Region : ' . ($location->regionName ?? 'N/A') .
                    ', Country : ' . ($location->countryName ?? 'N/A')
                    : 'Unknown',

                'path'     => '/login',
                'username' => $std->user_name,
            ]);
            $this->dispatch(
                'swal:alert',
                icon: 'success',
                title: 'Success',
                text: 'Login successfully!',
                url: route('student.dashboard')
            );

        }
        else
            $this->dispatch(
                'swal:alert',
                icon: 'error',
                title: 'Failed',
                text: 'Login Failed! Check Your Username/Password.'
            );
    }

    public function render()
    {
        return view('livewire.student.auth.student-login')->layout('layouts.app')->layoutData([
            'title' => 'Login',
        ]);
    }
}
