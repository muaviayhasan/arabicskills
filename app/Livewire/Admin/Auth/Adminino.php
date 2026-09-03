<?php

namespace App\Livewire\Admin\Auth;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Stevebauman\Location\Facades\Location;

class Adminino extends Component
{
    public $showPass = true;
    public $email;
    public $password;
    public function login()
    {
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
            $this->dispatch(
                'swal:alert',
                icon: 'success',
                title: 'Success',
                text: 'Login successfully!',
                url: route('admin.dashboard')
            );
        } else

            $this->dispatch(
                'swal:alert',
                icon: 'error',
                title: 'Failed',
                text: 'Login failed! Check your email Password.'
            );
    }
    public function render()
    {
        return view('livewire.admin.auth.adminino')->layout('layouts.auth-base')->layoutData([
            'title' => 'Adminino',
        ]);
    }
}
