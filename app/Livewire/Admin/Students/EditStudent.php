<?php

namespace App\Livewire\Admin\Students;

use App\Models\Exam;
use App\Models\Grade;
use App\Models\Level;
use App\Models\School;
use App\Models\Section;
use App\Models\Student;
use App\Support\StudentDemographics;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Livewire\Component;
use Livewire\WithFileUploads;

class EditStudent extends Component
{
    use WithFileUploads;

    public $inputs = [];

    public $schools;

    public $sections;

    public $grades;

    public $levels;

    public $student;

    public function mount($student)
    {
        $this->student = Student::withTrashed()->findOrFail($student);

        $this->inputs = $this->student->toArray();
        unset($this->inputs['image'], $this->inputs['user_name'], $this->inputs['year']);

        // The Yes/No selects hold "1" / "0" / "", not booleans.
        $this->inputs['gender'] = $this->student->gender ?? '';
        foreach (array_keys(StudentDemographics::FLAGS) as $column) {
            $this->inputs[$column] = StudentDemographics::toFormValue($this->student->{$column});
        }

        $this->schools = School::all();

        $this->sections = Section::where('grade_id', $this->inputs['grade_id'])->where('school_id', $this->inputs['school_id'])->get();

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
        $this->inputs['password'] = Str::random(15);
    }

    public function updateStudent()
    {

        $this->validate(array_merge([
            'inputs.name' => 'required',
            'inputs.registration' => 'required|unique:students,registration,'.$this->student->id.',id,year,'.$this->student->year,
            'inputs.level_id' => 'required|exists:levels,id',
            'inputs.password' => 'required|string',
            'inputs.nationality' => 'required|string',
            'inputs.category' => 'required|string',
            'inputs.school_id' => 'required',
            'inputs.section_id' => 'required',
            'inputs.grade_id' => 'required',
            'inputs.image' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
        ], StudentDemographics::formRules()));

        try {
            DB::beginTransaction();
            $path = '';
            if (isset($this->inputs['image'])) {
                $path = storage_path('app/public/images/'.$this->inputs['image']->hashName());

                $resizedImage = Image::make($this->inputs['image'])->encode('png', 100);

                $resizedImage->save($path, '80', 'png');

                delete_image($this->student->image);

                $this->inputs['image'] = create_image($this->inputs['image']->hashName());
            }

            unset($this->inputs['user_name'], $this->inputs['year']);

            $this->student->update(StudentDemographics::normaliseFormInputs($this->inputs));

            Exam::reconcileStudentExams($this->student->fresh());

            DB::commit();

            $this->dispatch(
                'swal:alert',
                icon: 'success',
                title: 'Student updated successfully',
                url: route('admin.students'),
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
        return view('livewire.admin.students.edit-student')->layout('layouts.base')->layoutData([
            'title' => 'Edit Student',
            'pageTitle' => 'Edit Student',
            'breadcrumb' => [
                'Dashboard' => route('admin.dashboard'),
                'Students' => route('admin.students'),
                'Edit Student' => '#',
            ],
        ]);
    }
}
