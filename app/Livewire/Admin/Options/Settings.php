<?php

namespace App\Livewire\Admin\Options;

use App\Models\Option;
use App\Support\MarkRanges;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Livewire\Component;
use Livewire\WithFileUploads;

class Settings extends Component
{
    use WithFileUploads;

    public $inputs = [];

    public $settings;

    public $old_image;

    protected $listeners = ['updateTabs'];

    public function updateTabs($key, $valu)
    {
        $this->inputs[$key] = $valu;
    }

    public function mount()
    {
        $this->settings = Option::query()
            ->where('key', '!=', 'levels')
            ->get();

        foreach ($this->settings as $setting) {
            if ($setting->key == 'logo') {
                $this->old_image = $setting->value;
            } elseif ($setting->key == 'terms') {
                $this->inputs[$setting->key] = unserialize($setting->value);
            } else {
                $this->inputs[$setting->key] = $setting->value;
            }
        }

        $this->inputs['current_academic_year'] ??= (string) (
            Option::where('key', 'current_academic_year')->value('value') ?: date('Y')
        );

        foreach (MarkRanges::DEFAULTS as $key => $default) {
            $this->inputs[$key] ??= $default;
        }

        if (! File::exists(storage_path('app/public/logo/'))) {
            File::makeDirectory(storage_path('app/public/logo/'), $mode = 0777, true, true);
        }
    }

    public function updateSettings()
    {

        $this->validate(array_merge([
            'inputs.web_name' => 'required|string',
            'inputs.web_email' => 'required|email',
            'inputs.logo' => 'nullable|sometimes|image',
            'inputs.terms' => 'required',
            'inputs.current_academic_year' => 'required|integer|min:2000|max:2100',
        ], MarkRanges::rules()), [], MarkRanges::attributes());

        try {
            DB::beginTransaction();

            $path = '';

            if (isset($this->inputs['logo']) && ! is_string($this->inputs['logo'])) {
                $path = storage_path('app/public/logo/'.$this->inputs['logo']->hashName());

                $resizedImage = Image::make($this->inputs['logo'])->resize(120, 120)->encode('png', 100);

                $resizedImage->save($path, '80', 'png');

                Storage::disk('public')->delete("logo/{$this->old_image}");

                $this->inputs['logo'] = $this->inputs['logo']->hashName();
            } else {
                $this->inputs['logo'] = $this->old_image;
            }

            $this->inputs['terms'] = serialize($this->inputs['terms']);

            $academicYear = (string) $this->inputs['current_academic_year'];

            Option::updateOrCreate(
                ['key' => 'current_academic_year'],
                ['value' => $academicYear]
            );

            config()->set('options.current_academic_year', $academicYear);

            // Saved explicitly, so they are stored even where no row exists yet.
            foreach (array_keys(MarkRanges::DEFAULTS) as $key) {
                Option::updateOrCreate(['key' => $key], ['value' => (string) $this->inputs[$key]]);
            }

            foreach ($this->settings as $setting) {
                if ($setting->key === 'current_academic_year' || array_key_exists($setting->key, MarkRanges::DEFAULTS)) {
                    continue;
                }

                $setting->update([
                    'value' => $this->inputs[$setting->key],
                ]);
            }

            DB::commit();
            MarkRanges::flush();

            $this->dispatch(
                'swal:alert',
                icon: 'success',
                title: 'Settings updated successfully',
                url: route('admin.settings'),
            );
        } catch (Exception $e) {
            DB::rollBack();
            if ($path) {
                @unlink(storage_path('app/'.$path));
            }

            $this->dispatch(
                'swal:alert',
                icon: 'error',
                title: 'Error',
                text: $e->getMessage(),
            );
        }
    }

    public function render()
    {
        return view('livewire.admin.options.settings')->layout('layouts.base')->layoutData([
            'title' => 'Site Settings',
            'pageTitle' => 'Site Settings',
            'breadcrumb' => [
                'Dashboard' => route('admin.dashboard'),
                'Site Settings' => route('admin.settings'),
            ],
        ]);
    }
}
