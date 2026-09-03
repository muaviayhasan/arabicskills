<?php

namespace App\Livewire\Admin\Profile;

use Exception;
use App\Models\Admin;
use Livewire\Component;
use App\Models\AdminRole;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;

class AdminProfile extends Component
{
    use WithFileUploads;
    public $admin   = [];
    public $adm;
    public $roles;
    public $old_img;


    public function mount()
    {

        $this->roles = AdminRole::all();
        $this->adm   = Admin::find(Auth::guard('admin')->user()->id);

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
        $this->validate([
            'admin.first_name' => 'required',
            'admin.last_name'  => 'required',
            'admin.email'      => 'required|email|unique:admins,email,' . $this->adm->id,
            'admin.password'   => 'nullable|string',
            'admin.image'      => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
        ]);
        try
        {

            DB::beginTransaction();

            if (isset($this->admin['image']) && ! is_string($this->admin['image']))
            {

                $path = storage_path('app/public/admins/' . $this->admin['image']->hashName());

                $resizedImage = Image::make($this->admin['image'])->resize(2084, 2084)->encode('png', 100);

                $resizedImage->save($path, '80', 'png');

                Storage::disk('public')->delete("admins/{$this->adm->image}");
                $this->old_img        = $this->admin['image'];
                $this->admin['image'] = $this->admin['image']->hashName();
            }

            $this->adm->update($this->admin);

            DB::commit();

            $this->dispatch(
                'swal:alert',
                icon: 'success',
                title: 'Profile updated successfully',
                url: route('admin.profile')
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
        return view('livewire.admin.profile.admin-profile')->layout('layouts.base')->layoutData([
            'title'      => 'Profile',
            'pageTitle'  => 'Profile',
            'breadcrumb' => [
                'Dashboard' => route('admin.dashboard'),
                'Profile'   => '#',
            ],
        ]);
    }
}
