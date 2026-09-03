<?php

namespace App\Livewire\Admin\Schools;

use Exception;
use App\Models\School;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\DB;
use Intervention\Image\Facades\Image;

class EditSchool extends Component
{
    use WithFileUploads;
    public $inputs = [];
    public $school;
    public $roles;

    public function mount($school_id)
    {

        $this->school = School::find($school_id);

        if (!$this->school)
            return back();

        $this->inputs = $this->school->toArray();

        unset($this->inputs['logo']);
    }

    public function updateSchool()
    {
        $this->validate([
            'inputs.name' => 'required|unique:schools,name,' . $this->school->id,
            'inputs.address' => 'required',
            'inputs.phone_number' => 'required',
            'inputs.email' => 'required|email',
            'inputs.principal' => 'required',
            'inputs.school_type' => 'required',
            'inputs.establishment_year' => 'required',
            'inputs.website' => 'required',
            'inputs.logo' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
        ]);

        try {
            DB::beginTransaction();

            if (isset($this->inputs['logo']) && !is_int($this->inputs['logo'])) {
                $path = storage_path('app/public/images/' . $this->inputs['logo']->hashName());

                $resizedlogo = Image::make($this->inputs['logo'])->resize(120, 120)->encode('png', 100);

                $resizedlogo->save($path, '80', 'png');

                delete_image($this->school->logo);
                $this->inputs['logo'] = create_image($this->inputs['logo']->hashName());
            }
            $this->school->update($this->inputs);

            DB::commit();
            $this->dispatch(
                'swal:alert',
                icon: 'success',
                title: 'School updated successfully',
                url: route('admin.schools')
            );
        } catch (Exception $e) {

            DB::rollback();
            @unlink($path);

            $this->dispatch(
                'swal:alert',
                icon: 'error',
                text: $e->getMessage(),
            );
        }
    }
    public function render()
    {
        return view('livewire.admin.schools.edit-school')->layout('layouts.base')->layoutData([
            'title' => 'Edit School',
            'pageTitle' => 'Edit School',
            'breadcrumb' => [
                'Dashboard' => route('admin.dashboard'),
                'Schools' => route('admin.schools'),
                'Edit School' => '#'
            ],
        ]);
    }
}
