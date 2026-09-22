<?php

namespace App\Livewire\Admin\AssessmentMarks;

use App\Exports\AssessmentTemplateExport;
use App\Exports\SkippedMarkRowsExport;
use App\Imports\AssessmentMarksImport;
use App\Livewire\Concerns\RestrictsToAdminSchool;
use App\Models\AssessmentMark;
use App\Models\School;
use App\Support\AssessmentRowResolver;
use App\Support\AssessmentSheet;
use App\Support\MarkRanges;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Upload a marks sheet: download the template, upload it, review every row,
 * then save. Nothing is written until the admin confirms.
 */
class ImportMarks extends Component
{
    use RestrictsToAdminSchool;
    use WithFileUploads;

    public $school_id;

    public $academic_year;

    public $file;

    /** Preview rows, built by import() and saved by confirmImport(). */
    public array $rows = [];

    public array $summary = [];

    public function mount(): void
    {
        $this->academic_year = current_academic_year();
        $this->school_id = $this->currentAdminSchoolId();
    }

    public function updated($property): void
    {
        // Changing what the sheet is for invalidates the preview.
        if (in_array($property, ['school_id', 'academic_year', 'file'], true)) {
            $this->rows = [];
            $this->summary = [];
        }
    }

    /** @return array<int, int> */
    public function years(): array
    {
        $current = current_academic_year();

        return range($current - 3, $current + 1);
    }

    public function downloadTemplate()
    {
        $this->validate([
            'school_id' => 'required|exists:schools,id',
            'academic_year' => 'required|integer',
        ]);

        $school = School::findOrFail($this->school_id);
        $students = AssessmentSheet::studentsFor((int) $this->school_id, (int) $this->academic_year);

        if ($students->isEmpty()) {
            $this->dispatch(
                'swal:alert',
                icon: 'error',
                text: "No students found for {$school->name} in {$this->academic_year}.",
            );

            return null;
        }

        $name = str($school->name)->slug().'-marks-'.$this->academic_year.'.xlsx';

        return Excel::download(
            new AssessmentTemplateExport(AssessmentSheet::rowsFor($students)),
            $name
        );
    }

    public function import(): void
    {
        $this->validate([
            'school_id' => 'required|exists:schools,id',
            'academic_year' => 'required|integer',
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        try {
            $reader = new AssessmentMarksImport;
            Excel::import($reader, $this->file);

            $rows = $reader->rows();

            if ($rows === []) {
                throw new Exception('That sheet has no rows.');
            }

            if (! array_key_exists(AssessmentSheet::STUDENT_ID_KEY, $rows[0])) {
                throw new Exception('That sheet has no "Student ID" column. Please use the downloaded template.');
            }

            $resolver = new AssessmentRowResolver((int) $this->academic_year);
            $preview = [];

            foreach ($rows as $index => $row) {
                $preview[] = $this->previewRow($resolver->resolve($row), $index + 2);
            }

            $this->rows = $preview;
            $this->summary = [];
        } catch (Exception $e) {
            $this->rows = [];
            $this->dispatch('swal:alert', icon: 'error', text: $e->getMessage());
        }
    }

    public function confirmImport(): void
    {
        if ($this->rows === []) {
            $this->dispatch('swal:toast', title: 'Upload a sheet first.', icon: 'error');

            return;
        }

        try {
            $saved = ['new' => 0, 'updated' => 0, 'unchanged' => 0];
            $students = 0;

            DB::transaction(function () use (&$saved, &$students) {
                foreach ($this->rows as $row) {
                    if ($row['status'] !== 'ready') {
                        continue;
                    }

                    $students++;

                    foreach ($row['rounds'] as $round => $details) {
                        $saved[$this->saveRound((int) $row['student_id'], (int) $round, $details['marks'])]++;
                    }
                }
            });

            $this->summary = $saved + ['students' => $students, 'skipped' => $this->problemCount()];

            $this->dispatch(
                'swal:alert',
                icon: 'success',
                title: 'Marks imported',
                text: "{$students} students. New: {$saved['new']}, updated: {$saved['updated']}, unchanged: {$saved['unchanged']}.",
            );
        } catch (Exception $e) {
            $this->dispatch('swal:alert', icon: 'error', text: $e->getMessage());
        }
    }

    public function downloadSkipped()
    {
        $rows = [];

        foreach ($this->rows as $row) {
            if ($row['status'] === 'ready') {
                continue;
            }

            $rows[] = [
                $row['row'],
                $row['registration'],
                $row['student_name'],
                implode(' ', $row['errors']),
            ];
        }

        if ($rows === []) {
            $this->dispatch('swal:toast', title: 'Nothing was skipped.', icon: 'success');

            return null;
        }

        return Excel::download(new SkippedMarkRowsExport($rows), 'skipped-marks-rows.xlsx');
    }

    public function problemCount(): int
    {
        return count(array_filter($this->rows, fn ($row) => $row['status'] !== 'ready'));
    }

    public function readyCount(): int
    {
        return count($this->rows) - $this->problemCount();
    }

    /**
     * Save one round, reporting whether anything actually changed so a
     * re-upload only touches marks that differ.
     */
    private function saveRound(int $studentId, int $round, array $marks): string
    {
        $values = $marks + ['total' => AssessmentMark::totalOf($marks)];

        $existing = AssessmentMark::where('student_id', $studentId)
            ->where('academic_year', $this->academic_year)
            ->where('round', $round)
            ->first();

        if (! $existing) {
            AssessmentMark::create($values + [
                'student_id' => $studentId,
                'academic_year' => $this->academic_year,
                'round' => $round,
                'admin_id' => Auth::guard('admin')->id(),
            ]);

            return 'new';
        }

        $unchanged = collect($values)->every(
            fn ($value, $column) => (float) $value === (float) $existing->{$column}
        );

        if ($unchanged) {
            return 'unchanged';
        }

        $existing->update($values + ['admin_id' => Auth::guard('admin')->id()]);

        return 'updated';
    }

    /** Shape one resolved row for the preview table. */
    private function previewRow(array $resolved, int $rowNumber): array
    {
        $student = $resolved['student'];
        $errors = $resolved['errors'];

        // The template is per school, so a student from elsewhere is a mistake.
        if ($student && (int) $student->school_id !== (int) $this->school_id) {
            $errors[] = 'Belongs to '.($student->School->name ?? 'another school').'.';
        }

        $rounds = [];

        foreach ($resolved['rounds'] as $round => $details) {
            $rounds[$round] = [
                'marks' => $details['marks'],
                'total' => $details['total'],
                'judgement' => MarkRanges::judge($details['total'], MarkRanges::TOTAL),
            ];
        }

        return [
            'row' => $rowNumber,
            'registration' => $resolved['registration'],
            'student_id' => $student?->id,
            'student_name' => $student?->name ?? '',
            'grade' => $student?->Grade->name ?? '',
            'section' => $student?->Section->name ?? '',
            'rounds' => $rounds,
            'errors' => $errors,
            'status' => ($errors === [] && $student && $rounds !== []) ? 'ready' : 'problem',
        ];
    }

    public function render()
    {
        $schools = $this->currentAdminSchoolId()
            ? School::where('id', $this->currentAdminSchoolId())->get()
            : School::orderBy('name')->get();

        return view('livewire.admin.assessment-marks.import-marks', [
            'schools' => $schools,
        ])->layout('layouts.base')->layoutData([
            'title' => 'Import Marks',
            'pageTitle' => 'Import Marks',
            'breadcrumb' => [
                'Dashboard' => route('admin.dashboard'),
                'Assessment Marks' => route('admin.assessment-marks'),
                'Import' => '#',
            ],
        ]);
    }
}
