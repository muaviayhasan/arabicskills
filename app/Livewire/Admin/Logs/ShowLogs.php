<?php

namespace App\Livewire\Admin\Logs;

use App\Livewire\Concerns\WithTableSorting;
use App\Models\IpLog;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class ShowLogs extends Component
{
    use WithPagination;
    use WithTableSorting;
    protected $paginationTheme = 'bootstrap';
    public $searchWord;
    public $searchColumn;
    protected $listeners = [
        'manageSearch'
    ];

    public function manageSearch($searchWord, $searchColumn)
    {
        $this->resetPage();
        $this->searchWord = $searchWord;
        $this->searchColumn = $searchColumn;
    }

    /**
     * @return array<string, string>
     */
    protected function sortableColumns(): array
    {
        return [
            'type' => 'loggable_type',
            'username' => 'username',
            'location' => 'location',
            'ip' => 'ip',
            'created_at' => 'created_at',
        ];
    }

    #[Computed]
    public function logs()
    {
        return IpLog::when($this->searchWord, function ($query) {
            $query->where('username', 'LIKE', "%{$this->searchWord}%");
        })

            ->tap(fn ($query) => $this->applySorting($query, 'created_at', 'desc'))
            ->paginate(20);
    }
    public function render()
    {



        return view('livewire.admin.logs.show-logs')->layout('layouts.base')->layoutData([
            'title' => 'Auth Logs',
            'pageTitle' => 'Auth Logs',
            'breadcrumb' => [
                'Dashboard' => route('admin.dashboard'),
                'Auth Logs' => "#",
            ],
        ]);
    }
}
