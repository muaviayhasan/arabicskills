<?php

namespace App\Livewire\Admin\Schools;

use App\Models\School;
use Exception;
use Illuminate\Support\Facades\DB;
use Intervention\Image\Facades\Image;
use Livewire\Component;
use Livewire\WithFileUploads;

class AddSchool extends Component
{
    use WithFileUploads;

    public $inputs = [];

    public function addSchool()
    {
        $this->validate([
            'inputs.name' => 'required|unique:schools,name',
            // 'inputs.address' => 'required',
            // 'inputs.phone_number' => 'required',
            // 'inputs.email' => 'required|email',
            // 'inputs.principal' => 'required',
            // 'inputs.school_type' => 'required',
            'inputs.establishment_year' => 'required',
            // 'inputs.website' => 'required',
            // 'inputs.logo' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
        ]);

        try {
            DB::beginTransaction();
            if (isset($this->inputs['logo']) && is_file($this->inputs['logo'])) {
                $path = storage_path('app/public/images/'.$this->inputs['logo']->hashName());

                $resizedImage = Image::make($this->inputs['logo'])->resize(120, 120)->encode('png', 100);

                $resizedImage->save($path, '80', 'png');

                $this->inputs['logo'] = create_image($this->inputs['logo']->hashName());
            }
            School::create($this->inputs);

            DB::commit();

            $this->dispatch(
                'swal:alert',
                icon: 'success',
                title: 'New school added successfully',
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
        return view('livewire.admin.schools.add-school')->layout('layouts.base')->layoutData([
            'title' => 'Add School',
            'pageTitle' => 'Add School',
            'breadcrumb' => [
                'Dashboard' => route('admin.dashboard'),
                'Schools' => route('admin.schools'),
                'Add School' => '#',
            ],
        ]);
    }
}
