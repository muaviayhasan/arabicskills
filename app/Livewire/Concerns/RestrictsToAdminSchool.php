<?php

namespace App\Livewire\Concerns;

use Illuminate\Support\Facades\Auth;

trait RestrictsToAdminSchool
{
    protected function currentAdminSchoolId(): ?int
    {
        $admin = Auth::guard('admin')->user();

        return $admin?->school_id;
    }

    protected function restrictToAdminSchoolId(?int $schoolId): bool
    {
        $adminSchoolId = $this->currentAdminSchoolId();

        return $adminSchoolId === null || (int) $adminSchoolId === (int) $schoolId;
    }
}
