<?php

namespace App\Livewire\Admin\Admins;

use Exception;
use App\Models\Admin;
use Livewire\Component;
use App\Models\AdminRole;
use App\Models\School;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Intervention\Image\Facades\Image;

class AddAdmin extends Component
{
    use WithFileUploads;
    public $admin = [];
    public $roles;
    public $schools;


    public function mount()
    {
        $this->admin = ['image' => null, 'school_id' => null];
        $this->roles = AdminRole::all();
        $this->schools = School::orderBy('name')->get();

        if (! File::exists(storage_path('app/public/admins/')))
        {
            File::makeDirectory(storage_path('app/public/admins/'), $mode = 0755, true, true);
        }
    }

    public function addAdmin()
    {
        if (($this->admin['school_id'] ?? null) === '') {
            $this->admin['school_id'] = null;
        }

        $this->validate([
            'admin.first_name' => 'required',
            'admin.last_name'  => 'required',
            'admin.email'      => 'required|email|unique:admins,email',
            'admin.password'   => 'required|string',
            'admin.role_id'    => 'required|numeric',
            'admin.school_id'  => 'nullable|integer|exists:schools,id',
            'admin.image'      => 'required|image|mimes:png,jpg,jpeg|max:2048',
        ]);

        try
        {
            DB::beginTransaction();

            $path = storage_path('app/public/admins/' . $this->admin['image']->hashName());

            $resizedImage = Image::make($this->admin['image'])->resize(2084, 2084)->encode('png', 100);

            $resizedImage->save($path, '80', 'png');


            $this->admin['image'] = $this->admin['image']->hashName();

            Admin::create($this->admin);

            DB::commit();

            $this->dispatch(
                'swal:alert',
                icon: 'success',
                title: 'New admin added successfully',
                url: route('admin.admins')
            );
        }
        catch (Exception $e)
        {

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
        return view('livewire.admin.admins.add-admin')->layout('layouts.base')->layoutData([
            'title'      => 'Add Admins',
            'pageTitle'  => 'Add Admins',
            'breadcrumb' => [
                'Dashboard'  => route('admin.dashboard'),
                'Admins'     => route('admin.admins'),
                'Add Admins' => '#',
            ],
        ]);
    }
}
