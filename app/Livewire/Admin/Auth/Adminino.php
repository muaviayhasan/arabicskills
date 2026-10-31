<?php

namespace App\Livewire\Admin\Auth;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Stevebauman\Location\Facades\Location;

class Adminino extends Component
{
    public $email;
    public $password;

    /**
     * Shown above the form when the credentials are refused. It sits with the
     * fields rather than in a dialog, so there is nothing to dismiss before
     * typing again.
     */
    public ?string $failure = null;

    public function login()
    {
        $this->failure = null;

        $this->validate([
            'email' => 'required|email',
            'password' => "required"
        ]);
        if (Auth::guard('admin')->attempt([
            'email' => $this->email,
            'password' => $this->password,
            'deleted_at' => null
        ])) {
            $auth = Auth::guard('admin')->user();
            $ip = request()->ip();
            $location = Location::get($ip);

            $auth->logs()->create([
                'ip' => $ip,
                'location' => $location
                    ? 'City : ' . ($location->cityName ?? 'N/A') .
                    ', Region : ' . ($location->regionName ?? 'N/A') .
                    ', Country : ' . ($location->countryName ?? 'N/A')
                    : 'Unknown',

                'path' => '/adminino',
                'username' => $auth->email,
            ]);

            return redirect()->route('admin.dashboard');
        }

        $this->reset('password');
        $this->failure = 'We could not sign you in. Check the email address and password, then try again.';
    }
    public function render()
    {
        return view('livewire.admin.auth.adminino')->layout('layouts.auth-base')->layoutData([
            'title' => 'Sign in',
        ]);
    }
}
