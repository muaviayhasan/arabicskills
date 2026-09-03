<?php

namespace App\Livewire\Admin\DeviceTests;

use Livewire\Component;
use App\Models\DeviceTest;

class DeviceTestDetails extends Component
{
    public $test;
    public $questions;

    public function mount($id)
    {
        $this->test = DeviceTest::with('student')->findOrFail($id);

        // Instantiate the test component just to grab the fake questions structure
        $deviceTestComponent = new \App\Livewire\Student\DeviceTest();
        $deviceTestComponent->mount();
        $this->questions = collect($deviceTestComponent->fakeQuestions)->keyBy('id')->toArray();
    }

    public function render()
    {
        return view('livewire.admin.device-tests.device-test-details')->layout('layouts.base')->layoutData([
            'title' => 'Device Test Details',
                    'pageTitle' => 'Device Test Details',
                    'breadcrumb' => [
                        'Dashboard' => route('admin.dashboard'),
                        'Device Test Details' => "#",
                    ],
        ]);
    }
}
