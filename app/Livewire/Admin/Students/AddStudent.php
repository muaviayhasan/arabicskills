<?php

namespace App\Livewire\Admin\Students;

use App\Models\Exam;
use App\Models\Grade;
use App\Models\Level;
use App\Models\School;
use App\Models\Section;
use App\Models\Student;
use App\Support\StudentUsername;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Livewire\Component;
use Livewire\WithFileUploads;

class AddStudent extends Component
{
    use WithFileUploads;

    public $inputs = [];

    public $schools;

    public $sections;

    public $grades;

    public $levels;

    public function mount($school_id = null)
    {
        $this->inputs['school_id'] = $school_id;
        if ($school_id) {
            $this->schools = School::where('id', $school_id)->get();
            $this->sections = Section::where('grade_id', $this->inputs['grade_id'])->where('school_id', $this->inputs['school_id'])->get();
        } else {
            $this->schools = School::all();
        }

        $this->grades = Grade::query()
            ->whereNotNull('number')
            ->orderBy('number')
            ->get(['id', 'name', 'number']);

        $this->levels = Level::query()
            ->orderBy('number')
            ->get(['id', 'name', 'number']);
    }

    public function updateSections()
    {
        $this->sections = Section::where('grade_id', $this->inputs['grade_id'])->where('school_id', $this->inputs['school_id'])->get();
    }

    public function makePass()
    {
        $this->inputs['password'] = Str::random(10);
    }

    public function addStudent()
    {
        $year = current_academic_year();

        $this->validate([
            'inputs.name' => 'required',
            'inputs.registration' => 'required|unique:students,registration,NULL,id,year,'.$year,
            'inputs.level_id' => 'required|exists:levels,id',
            'inputs.password' => 'required|string',
            'inputs.nationality' => 'required|string',
            'inputs.category' => 'required|string',
            'inputs.school_id' => 'required',
            'inputs.section_id' => 'required',
            'inputs.grade_id' => 'required',
            'inputs.image' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
        ]);

        try {
            DB::beginTransaction();
            $path = '';
            if (isset($this->inputs['image'])) {
                $path = storage_path('app/public/images/'.$this->inputs['image']->hashName());

                $resizedImage = Image::make($this->inputs['image'])->resize(120, 120)->encode('png', 100);

                $resizedImage->save($path, '80', 'png');

                $this->inputs['image'] = create_image($this->inputs['image']->hashName());
            }

            $this->inputs['year'] = $year;
            $this->inputs['user_name'] = StudentUsername::forNewStudent($this->inputs['registration'], $year);

            $std = Student::create($this->inputs);

            Exam::reconcileStudentExams($std);

            DB::commit();

            $this->dispatch(
                'swal:alert',
                icon: 'success',
                title: 'New student added successfully',
                url: url()->previous(),
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
        return view('livewire.admin.students.add-student')->layout('layouts.base')->layoutData([
            'title' => 'Add Student',
            'pageTitle' => 'Add Student',
            'breadcrumb' => [
                'Dashboard' => route('admin.dashboard'),
                'Students' => route('admin.students'),
                'Add Student' => '#',
            ],
        ]);
    }
}
