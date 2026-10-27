<?php

namespace Tests\Feature;

use App\Imports\StudentsImport;
use App\Livewire\Admin\Students\ImportStudent;
use App\Models\Admin;
use App\Models\Grade;
use App\Models\Level;
use App\Models\School;
use App\Models\Student;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/**
 * Importing the same file twice must update the students it already created,
 * never make a second copy of them.
 */
class StudentImportReuploadTest extends TestCase
{
    use DatabaseTransactions;

    private School $school;

    private Grade $grade;

    private Level $level;

    protected function setUp(): void
    {
        parent::setUp();

        $school = School::query()->orderBy('id')->first();
        $grade = Grade::query()->whereNotNull('number')->orderBy('number')->first();
        $level = Level::query()->orderBy('number')->first();

        if ($school === null || $grade === null || $level === null) {
            $this->markTestSkipped('Need a school, a numbered grade and a level.');
        }

        $this->school = $school;
        $this->grade = $grade;
        $this->level = $level;

        $admin = Admin::query()->first();
        if ($admin === null) {
            $this->markTestSkipped('Need an admin to act as.');
        }
        $this->actingAs($admin, 'admin');
    }

    /**
     * @param  list<array<int, string>>  $rows
     */
    private function sheet(array $rows, ?array $headings = null): UploadedFile
    {
        $headings ??= [
            'Student Name', 'Registration', 'UserName', 'Password', 'School Name',
            'Grade/year', 'Division', 'Level', 'Nationality', 'Category',
        ];

        $book = new Spreadsheet;
        $book->getActiveSheet()->fromArray(array_merge([$headings], $rows), null, 'A1');

        $path = tempnam(sys_get_temp_dir(), 'imp').'.xlsx';
        (new Xlsx($book))->save($path);

        return new UploadedFile($path, 'students.xlsx', null, null, true);
    }

    private function row(string $reg, string $name): array
    {
        return [
            $name, $reg, '', '', $this->school->name,
            $this->grade->name, 'A', $this->level->name, 'Indian', 'Boys',
        ];
    }

    /**
     * Livewire's test upload helper reads $file->name, which a plain
     * UploadedFile does not have, so the component is driven directly. The
     * import logic is the same either way.
     */
    private function runImport(UploadedFile $file): ImportStudent
    {
        $component = Livewire::test(ImportStudent::class)
            ->set('school_id', $this->school->id)
            ->instance();

        $component->file = $file;
        $component->import();
        $component->addStudents();

        return $component;
    }

    private function countFor(string $reg): int
    {
        return Student::withTrashed()->where('registration', $reg)->count();
    }

    public function test_importing_the_same_file_twice_does_not_duplicate(): void
    {
        $reg = 'ZZ'.random_int(100000, 999999);

        $this->runImport($this->sheet([$this->row($reg, 'First Upload')]));
        $this->assertSame(1, $this->countFor($reg), 'first import should create one student');

        $this->runImport($this->sheet([$this->row($reg, 'First Upload')]));
        $this->assertSame(1, $this->countFor($reg), 'second import must not create a second copy');
    }

    /**
     * The old lookup was built from the academic year, so the same file imported
     * in a later year matched nothing and duplicated every student.
     */
    public function test_a_later_academic_year_still_matches_the_same_student(): void
    {
        $reg = 'ZZ'.random_int(100000, 999999);

        $this->runImport($this->sheet([$this->row($reg, 'Original Name')]));
        $student = Student::withTrashed()->where('registration', $reg)->firstOrFail();

        // whatever username year the row was first written under
        $student->update(['user_name' => 'AST-Y19-'.$reg]);

        $this->runImport($this->sheet([$this->row($reg, 'Updated Name')]));

        $this->assertSame(1, $this->countFor($reg));
        $this->assertSame('Updated Name', Student::withTrashed()->where('registration', $reg)->first()->name);
    }

    public function test_an_archived_student_is_restored_rather_than_duplicated(): void
    {
        $reg = 'ZZ'.random_int(100000, 999999);

        $this->runImport($this->sheet([$this->row($reg, 'Archived Child')]));
        Student::where('registration', $reg)->firstOrFail()->delete();

        $this->runImport($this->sheet([$this->row($reg, 'Archived Child')]));

        $this->assertSame(1, $this->countFor($reg));
        $this->assertFalse(
            Student::withTrashed()->where('registration', $reg)->first()->trashed(),
            'the student should be back in the active list'
        );
    }

    /**
     * Two schools numbering their pupils independently must not take each
     * other's records over: the import reports it instead.
     */
    public function test_a_registration_used_by_another_school_is_reported(): void
    {
        $other = School::query()->where('id', '!=', $this->school->id)->first();
        if ($other === null) {
            $this->markTestSkipped('Need a second school.');
        }

        $reg = 'ZZ'.random_int(100000, 999999);

        $theirs = Student::create([
            'name' => 'Belongs Elsewhere',
            'school_id' => $other->id,
            'grade_id' => $this->grade->id,
            'level_id' => $this->level->id,
            'registration' => $reg,
            'user_name' => 'AST-Y26-'.$reg,
            'password' => 'x',
            'year' => date('Y'),
        ]);

        $this->runImport($this->sheet([$this->row($reg, 'Different Child')]));

        $this->assertSame(1, $this->countFor($reg), 'no second student may be created');
        $this->assertSame(
            $other->id,
            $theirs->fresh()->school_id,
            'the other school must keep its student'
        );
    }

    public function test_a_file_missing_required_columns_names_them(): void
    {
        $file = $this->sheet(
            [['Someone', 'ZZ1', '', '', $this->grade->name, 'A', 'Indian', 'Boys']],
            ['Student Name', 'Registration', 'UserName', 'Password', 'Grade/year', 'Division', 'Nationality', 'Category']
        );

        $component = Livewire::test(ImportStudent::class)
            ->set('school_id', $this->school->id)
            ->instance();

        $component->file = $file;
        $component->import();

        // nothing is staged for saving when the columns do not match
        $this->assertSame([], $component->students);

        $import = new StudentsImport;
        Excel::import($import, $file);
        $this->assertSame(['School Name', 'Level'], $import->missingColumns());
    }
}
