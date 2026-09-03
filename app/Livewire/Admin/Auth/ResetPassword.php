<?php

namespace App\Livewire\Admin\Auth;

use Livewire\Component;

class ResetPassword extends Component
{
    public function render()
    {
        return view('livewire.admin.auth.reset-password')->layout('layouts.auth-base')->layoutData([
            'title' => 'Reset Password',
        ]);
    }
}
