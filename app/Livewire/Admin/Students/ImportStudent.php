<?php

namespace App\Livewire\Admin\Students;

use App\Imports\StudentsImport;
use App\Models\Exam;
use App\Models\School;
use App\Models\Section;
use App\Models\Student;
use App\Support\StudentImportRowResolver;
use App\Support\StudentUsername;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;

class ImportStudent extends Component
{
    use WithFileUploads;

    public $file;

    public $students = [];

    public $school;

    public $school_id;

    public $schools;

    public function mount($school_id = null)
    {
        $this->school_id = $school_id;
        $this->schools = School::all();
    }

    /**
     * ======================
     * IMPORT CSV
     * ======================
     */
    public function import()
    {
        $this->validate([
            'school_id' => 'required',
            'file' => 'required|file',
        ]);

        try {
            $import = new StudentsImport;
            Excel::import($import, $this->file);

            $importedStudents = $import->getStudents();
            $this->students = $importedStudents;

            if (empty($importedStudents)) {
                throw new Exception('No students found in uploaded file.');
            }

            $importedSchoolNames = collect($importedStudents)
                ->pluck('school')
                ->map(fn ($name) => strtolower(trim((string) $name)))
                ->unique();

            if ($importedSchoolNames->count() > 1) {
                $this->students = [];
                throw new Exception(
                    'All students must belong to the same school. Found: '.
                    $importedSchoolNames->implode(', ')
                );
            }

            $this->school = School::find($this->school_id);

            if (! $this->school) {
                $this->students = [];
                throw new Exception('Selected school not found.');
            }

            $importedSchool = $importedSchoolNames->first();
            $selectedSchool = strtolower(trim($this->school->name));

            if (
                empty($importedSchool) ||
                (
                    $importedSchool !== $selectedSchool &&
                    ! str_contains($selectedSchool, $importedSchool) &&
                    ! str_contains($importedSchool, $selectedSchool)
                )
            ) {
                $this->students = [];
                throw new Exception(
                    "Imported school '{$importedSchool}' does not match selected school '{$this->school->name}'."
                );
            }

            $this->students = $this->attachResolutionPreview($importedStudents);

        } catch (Exception $e) {
            $this->dispatch(
                'swal:alert',
                icon: 'error',
                text: $e->getMessage(),
            );
        }
    }

    /**
     * ======================
     * ADD STUDENTS
     * ======================
     */
    public function addStudents()
    {
        if (! count($this->students)) {
            $this->dispatch(
                'swal:toast',
                title: 'First import csv.',
                icon: 'error',
            );

            return;
        }

        try {
            $errors = [];
            DB::beginTransaction();

            $normalize = function ($value, string $default = ''): array {
                $value = (string) ($value ?? '');
                $value = str_replace(["\xC2\xA0", "\u{00A0}"], ' ', $value);
                $value = preg_replace('/\s+/u', ' ', trim($value));
                $value = str_replace(['–', '—'], '-', $value);
                $value = preg_replace('/\s*-\s*/u', ' - ', $value);

                if ($value === '') {
                    return ['', $default];
                }

                return [
                    strtolower($value),
                    Str::title(Str::lower($value)),
                ];
            };

            $rowResolver = new StudentImportRowResolver;

            $sectionMap = Section::where('school_id', $this->school->id)
                ->get(['id', 'grade_id', 'name'])
                ->mapWithKeys(function ($section) use ($normalize) {
                    [$key] = $normalize($section->name);

                    return [$section->grade_id.'|'.$key => $section->id];
                })
                ->toArray();

            $importYear = current_academic_year();

            foreach ($this->students as $i => $student) {
                $row = $i + 1;

                if (empty($student['registration'])) {
                    $errors[] = "Row {$row}: Registration missing.";

                    continue;
                }

                $csvLevel = trim((string) ($student['level'] ?? ''));
                $resolved = $rowResolver->resolve($student['grade'] ?? '', $csvLevel);

                if ($resolved['level_id'] === null || $resolved['grade_id'] === null) {
                    $errors[] = "Row {$row}: ".implode(' ', $resolved['errors']);

                    continue;
                }

                $levelId = $resolved['level_id'];
                $gradeId = $resolved['grade_id'];

                [$sectionKey, $sectionTitle] = $normalize($student['section'], 'Unknown Section');
                $sectionMapKey = $gradeId.'|'.$sectionKey;

                if (! isset($sectionMap[$sectionMapKey])) {
                    $section = Section::create([
                        'school_id' => $this->school->id,
                        'grade_id' => $gradeId,
                        'name' => $sectionTitle,
                    ]);
                    $sectionMap[$sectionMapKey] = $section->id;
                }

                $registration = trim((string) $student['registration']);

                $firstName = strtolower(
                    preg_replace(
                        '/[^a-z0-9]/i',
                        '',
                        Str::of($student['name'] ?? '')
                            ->trim()
                            ->explode(' ')
                            ->first() ?? ''
                    )
                );

                $password = ! empty($student['password'])
                    ? $student['password']
                    : (($firstName ?: 'student').'@'.$registration);

                $legacyUsername = StudentUsername::legacy($registration);
                $newUsername = StudentUsername::forNewStudent($registration, $importYear);

                $existing = Student::whereIn('user_name', [$legacyUsername, $newUsername])->first();

                $studentData = [
                    'name' => $student['name'],
                    'school_id' => $this->school_id,
                    'grade_id' => $gradeId,
                    'section_id' => $sectionMap[$sectionMapKey],
                    'level_id' => $levelId,
                    'nationality' => $student['nationality'] ?? '',
                    'password' => $password,
                    'category' => $student['category'] ?? '',
                ];

                if ($existing) {
                    $existing->update($studentData);
                    Exam::reconcileStudentExams($existing->fresh());
                } else {
                    $created = Student::create(array_merge($studentData, [
                        'registration' => $registration,
                        'year' => $importYear,
                        'user_name' => $newUsername,
                    ]));
                    Exam::reconcileStudentExams($created);
                }
            }

            if ($errors) {
                DB::rollBack();
                throw new Exception(
                    "Import failed:\n".
                    implode("\n", array_slice($errors, 0, 10)).
                    (count($errors) > 10 ? "\n...and more." : '')
                );
            }

            DB::commit();

            $this->dispatch(
                'swal:alert',
                icon: 'success',
                text: 'Students record imported',
                url: route('admin.students')
            );

        } catch (Exception $e) {
            DB::rollBack();
            $this->dispatch(
                'swal:alert',
                icon: 'error',
                text: $e->getMessage(),
            );
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    protected function attachResolutionPreview(array $rows): array
    {
        $rowResolver = new StudentImportRowResolver;

        foreach ($rows as $i => $row) {
            $resolved = $rowResolver->resolve($row['grade'] ?? '', $row['level'] ?? '');

            $rows[$i]['_resolved_level_id'] = $resolved['level_id'];
            $rows[$i]['_resolved_grade_id'] = $resolved['grade_id'];
            $rows[$i]['_resolved_level_name'] = $resolved['level_name'];
            $rows[$i]['_resolved_grade_name'] = $resolved['grade_name'];
            $rows[$i]['_resolution_errors'] = $resolved['errors'];
        }

        return $rows;
    }

    public function render()
    {
        return view('livewire.admin.students.import-student')
            ->layout('layouts.base')
            ->layoutData([
                'title' => 'Import students',
                'pageTitle' => 'Import students',
                'breadcrumb' => [
                    'Dashboard' => route('admin.dashboard'),
                    'Students' => route('admin.students'),
                    'Import students' => '#',
                ],
            ]);
    }
}
