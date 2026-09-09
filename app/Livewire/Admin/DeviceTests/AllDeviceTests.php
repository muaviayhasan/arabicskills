<?php

namespace App\Livewire\Admin\DeviceTests;

use Livewire\Component;
use Livewire\WithPagination;
use App\Livewire\Concerns\WithTableSorting;
use App\Models\DeviceTest;

class AllDeviceTests extends Component
{
    use WithPagination;
    use WithTableSorting;
    
    protected $paginationTheme = 'bootstrap';

    public $search = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    /**
     * @return array<string, string>
     */
    protected function sortableColumns(): array
    {
        return [
            'student' => 'students.name',
            'registration' => 'students.registration',
            'status' => 'device_tests.overall_status',
            'tested_at' => 'device_tests.created_at',
        ];
    }

    protected function applySortJoins($query)
    {
        $query->select('device_tests.*');

        return in_array($this->sortField, ['student', 'registration'], true)
            ? $query->leftJoin('students', 'students.id', '=', 'device_tests.student_id')
            : $query;
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
            ->tap(fn ($q) => $this->applySorting($q, 'device_tests.created_at', 'desc'))
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
