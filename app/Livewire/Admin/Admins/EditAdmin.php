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
use Illuminate\Support\Facades\Storage;

class EditAdmin extends Component
{
    use WithFileUploads;
    public $admin = [];
    public $adm;
    public $roles;
    public $schools;

    public function mount($admin_id)
    {

        $this->roles = AdminRole::all();
        $this->schools = School::orderBy('name')->get();

        $this->adm = Admin::find($admin_id);

        if (! $this->adm)
            return back();

        $this->admin = $this->adm->toArray();

        unset($this->admin['password'], $this->admin['image']);

        if (! File::exists(storage_path('app/public/admins/')))
        {
            File::makeDirectory(storage_path('app/public/admins/'), $mode = 0755, true, true);
        }
    }

    public function updateAdmin()
    {
        if (($this->admin['school_id'] ?? null) === '') {
            $this->admin['school_id'] = null;
        }

        $this->validate([
            'admin.first_name' => 'required',
            'admin.last_name'  => 'required',
            'admin.email'      => 'required|email|unique:admins,email,' . $this->adm->id,
            'admin.password'   => 'nullable|string',
            'admin.role_id'    => 'required|numeric',
            'admin.school_id'  => 'nullable|integer|exists:schools,id',
            'admin.image'      => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
        ]);

        $path = null;
        try
        {
            DB::beginTransaction();

            if (isset($this->admin['image']) && ! is_string($this->admin['image']))
            {
                $path = storage_path('app/public/admins/' . $this->admin['image']->hashName());

                $resizedImage = Image::make($this->admin['image'])->resize(2084, 2084)->encode('png', 100);

                $resizedImage->save($path, '80', 'png');


                Storage::disk('public')->delete("admins/{$this->adm->image}");
                $this->admin['image'] = $this->admin['image']->hashName();
            }
            $this->adm->update($this->admin);

            DB::commit();
            $this->dispatch(
                'swal:alert',
                icon: 'success',
                title: 'Admin updated successfully',
                url: route('admin.admins')
            );
        }
        catch (Exception $e)
        {

            DB::rollback();
            if ($path) {
                @unlink($path);
            }

            $this->dispatch(
                'swal:alert',
                icon: 'error',
                text: $e->getMessage(),
            );
        }
    }
    public function render()
    {
        return view('livewire.admin.admins.edit-admin')->layout('layouts.base')->layoutData([
            'title'      => 'Edit Admins',
            'pageTitle'  => 'Edit Admins',
            'breadcrumb' => [
                'Dashboard'   => route('admin.dashboard'),
                'Admins'      => route('admin.admins'),
                'Edit Admins' => '#',
            ],
        ]);
    }
}
