<?php

namespace App\Livewire\Admin\Auth;

use Livewire\Component;

class RequestPassword extends Component
{
    public function render()
    {
        return view('livewire.admin.auth.request-password')->layout('layouts.auth-base')->layoutData([
            'title' => 'Request Password',
        ]);
    }
}
