<?php

namespace App\Livewire\Student;

use Livewire\Component;
use App\Models\DeviceTest;

class DeviceTestResult extends Component
{
    public $test;
    public $questions;

    public function mount($id)
    {
        $this->test = DeviceTest::where('id', $id)
            ->where('student_id', auth()->user()->id)
            ->firstOrFail();

        // Instantiate the test component just to grab the fake questions structure
        $deviceTestComponent = new \App\Livewire\Student\DeviceTest();
        $deviceTestComponent->mount();
        $this->questions = collect($deviceTestComponent->fakeQuestions)->keyBy('id')->toArray();
    }

    public function render()
    {
        return view('livewire.student.device-test-result')->layout('layouts.app')->layoutData([
            'title' => 'Device Test Results',
        ]);
    }
}
