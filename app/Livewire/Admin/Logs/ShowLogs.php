<?php

namespace App\Livewire\Admin\Logs;

use App\Models\IpLog;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class ShowLogs extends Component
{
    use WithPagination;
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

    #[Computed]
    public function logs()
    {
        return IpLog::when($this->searchWord, function ($query) {
            $query->where('username', 'LIKE', "%{$this->searchWord}%");
        })

            ->orderByDESC('created_at')
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
