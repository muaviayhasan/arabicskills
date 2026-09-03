<?php

namespace App\Livewire\Admin\DeviceTests;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\DeviceTest;

class AllDeviceTests extends Component
{
    use WithPagination;
    
    protected $paginationTheme = 'bootstrap';

    public $search = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $tests = DeviceTest::with('student')
            ->when($this->search, function ($query) {
                $query->whereHas('student', function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('registration', 'like', '%' . $this->search . '%');
                });
            })
            ->latest()
            ->paginate(15);

        return view('livewire.admin.device-tests.all-device-tests', [
            'tests' => $tests
        ])->layout('layouts.base')->layoutData([
                    'title' => 'Device Tests',
                    'pageTitle' => 'Device Tests',
                    'breadcrumb' => [
                        'Dashboard' => route('admin.dashboard'),
                        'Device Tests' => "#",
                    ],
                ]);
    }
}
