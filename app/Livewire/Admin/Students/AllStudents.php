<?php

namespace App\Livewire\Admin\Students;

use App\Exports\StudentExport;
use App\Livewire\Concerns\WithTableSorting;
use App\Models\Grade;
use App\Models\Level;
use App\Models\School;
use App\Models\Section;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use ZipArchive;

class AllStudents extends Component
{
    use WithPagination;
    use WithTableSorting;

    protected $paginationTheme = 'bootstrap';

    public $school_id;

    public $grade_id;

    public $section;

    public $schools;

    public $grades;

    public $level_id;

    public $searchWord;

    public $searchColumn = 'global';

    public $year;

    public $archiveStatus = 'active';

    protected $listeners = [
        'archiveStudentConfirm',
        'bulkArchiveStudentsConfirm',
        'restoreStudentConfirm',
        'bulkRestoreStudentsConfirm',
        'manageSearch',
    ];

    protected $queryString = [
        'school_id' => ['except' => ''],
        'grade_id' => ['except' => ''],
        'section' => ['except' => ''],
        'level_id' => ['except' => ''],
        'searchWord' => ['except' => ''],
        'year' => ['except' => ''],
        'archiveStatus' => ['except' => 'active'],
    ];

    public function mount($school_id = null)
    {
        $this->searchWord = $this->searchWord ?? '';

        if ($school_id != null) {
            $this->school_id = (int) $school_id;
        }

        $this->schools = School::query()->orderBy('name')->get(['id', 'name']);
        $this->grades = Grade::query()
            ->whereNotNull('number')
            ->orderBy('number')
            ->get(['id', 'name', 'number']);
    }

    public function manageSearch($searchWord = null, $searchColumn = null)
    {
        $this->resetPage();

        // Backward compatible: allow external event to set search column/word.
        if ($searchWord !== null || $searchColumn !== null) {
            $this->searchWord = (string) ($searchWord ?? '');
            $this->searchColumn = (string) ($searchColumn ?? 'global');
        }
    }

    public function resetFilters()
    {
        $this->resetPage();
        $this->school_id = null;
        $this->grade_id = null;
        $this->section = null;
        $this->searchWord = '';
        $this->searchColumn = 'global';
        $this->level_id = '';
        $this->year = null;
        $this->archiveStatus = 'active';
    }

    public function updatedSchoolId()
    {
        $this->section = null;
    }

    public function updatedGradeId()
    {
        $this->section = null;
    }

    public function archiveStudent($id)
    {
        $this->dispatch(
            'confirmDelete',

            text: 'This student will be archived and hidden from active lists and login.',
            id: $id,
            emitBack: 'archiveStudentConfirm',
        );
    }

    public function archiveStudentConfirm($id)
    {
        $student = Student::find($id);

        if (! $student) {
            return;
        }

        $student->delete();

        $this->dispatch(
            'swal:toast',
            title: 'Student archived successfully.',
            icon: 'success',
        );
    }

    public function bulkArchiveStudents()
    {
        if (! getPermissions('students', 'delete')) {
            return;
        }

        $count = $this->bulkArchiveEligibleQuery()->count();

        if ($count === 0) {
            $this->dispatch(
                'swal:toast',
                title: 'No active students match the current filters to archive.',
                icon: 'warning',
            );

            return;
        }

        $this->dispatch(
            'confirmBulkArchive',
            text: "All {$count} active student(s) matching the applied filters will be archived. "
                .'They will be hidden from active lists and login. You can restore them later.',
            emitBack: 'bulkArchiveStudentsConfirm',
        );
    }

    public function bulkArchiveStudentsConfirm()
    {
        if (! getPermissions('students', 'delete')) {
            return;
        }

        $query = $this->bulkArchiveEligibleQuery();
        $archived = 0;

        DB::transaction(function () use ($query, &$archived) {
            $query->chunkById(200, function ($students) use (&$archived) {
                foreach ($students as $student) {
                    if ($student->trashed()) {
                        continue;
                    }

                    $student->delete();
                    $archived++;
                }
            });
        });

        $this->resetPage();

        if ($archived === 0) {
            $this->dispatch(
                'swal:alert',
                icon: 'warning',
                title: 'No students archived',
                text: 'No active students matched the current filters.',
            );

            return;
        }

        $this->dispatch(
            'swal:alert',
            icon: 'success',
            title: 'Bulk archive complete',
            text: "{$archived} student(s) were archived successfully.",
        );
    }

    protected function bulkArchiveEligibleQuery()
    {
        if ($this->archiveStatus === 'archived') {
            return Student::query()->whereRaw('0 = 1');
        }

        $query = $this->LoadStudents();

        if ($this->archiveStatus === 'all') {
            $query->whereNull('students.deleted_at');
        }

        return $query;
    }

    public function restoreStudent($id)
    {
        $this->dispatch(
            'confirmRestore',
            text: 'This student will be restored and can log in again.',
            id: $id,
            emitBack: 'restoreStudentConfirm',
        );
    }

    public function restoreStudentConfirm($id)
    {
        $student = Student::withTrashed()->find($id);

        if (! $student || ! $student->trashed()) {
            return;
        }

        if ($student->hasActiveRegistrationConflict()) {
            $this->dispatch(
                'swal:alert',
                icon: 'error',
                title: 'Cannot restore student',
                text: 'An active student already exists with registration '
                    .$student->registration.' for year '.$student->year
                    .'. Edit this archived student and update the registration number before restoring.',
            );

            return;
        }

        $student->restore();

        $this->dispatch(
            'swal:toast',
            title: 'Student restored successfully.',
            icon: 'success',
        );
    }

    public function bulkRestoreStudents()
    {
        if (! getPermissions('students', 'delete')) {
            return;
        }

        $count = $this->bulkRestoreEligibleQuery()->count();

        if ($count === 0) {
            $this->dispatch(
                'swal:toast',
                title: 'No archived students match the current filters to restore.',
                icon: 'warning',
            );

            return;
        }

        $this->dispatch(
            'confirmBulkRestore',
            text: "All {$count} archived student(s) matching the applied filters will be restored.",
            emitBack: 'bulkRestoreStudentsConfirm',
        );
    }

    public function bulkRestoreStudentsConfirm()
    {
        if (! getPermissions('students', 'delete')) {
            return;
        }

        $query = $this->bulkRestoreEligibleQuery();
        $restored = 0;
        $skipped = 0;

        DB::transaction(function () use ($query, &$restored, &$skipped) {
            $query->chunkById(200, function ($students) use (&$restored, &$skipped) {
                foreach ($students as $student) {
                    if (! $student->trashed()) {
                        continue;
                    }

                    if ($student->hasActiveRegistrationConflict()) {
                        $skipped++;

                        continue;
                    }

                    $student->restore();
                    $restored++;
                }
            });
        });

        $this->resetPage();

        if ($restored === 0 && $skipped === 0) {
            $this->dispatch(
                'swal:alert',
                icon: 'warning',
                title: 'No students restored',
                text: 'No archived students matched the current filters.',
            );

            return;
        }

        if ($restored === 0 && $skipped > 0) {
            $this->dispatch(
                'swal:alert',
                icon: 'error',
                title: 'Cannot restore students',
                text: "{$skipped} student(s) were skipped because an active student already exists with the same registration and year. Edit each archived student and update the registration number before restoring.",
            );

            return;
        }

        $text = "{$restored} student(s) were restored successfully.";
        if ($skipped > 0) {
            $text .= " {$skipped} student(s) were skipped because an active student already exists with the same registration and year.";
        }

        $this->dispatch(
            'swal:alert',
            icon: $skipped > 0 ? 'warning' : 'success',
            title: 'Bulk restore complete',
            text: $text,
        );
    }

    protected function bulkRestoreEligibleQuery()
    {
        if ($this->archiveStatus === 'active') {
            return Student::query()->whereRaw('0 = 1');
        }

        $query = $this->LoadStudents();

        if ($this->archiveStatus === 'all') {
            $query->whereNotNull('students.deleted_at');
        }

        return $query;
    }

    public function downloadQrsZip()
    {
        set_time_limit(0);

        $schoolName = null;
        if ($this->school_id) {
            $schoolName = School::whereKey($this->school_id)->value('name');
        }

        // Include FK columns so eager-loaded relations work for label lines.
        $students = $this->LoadStudents()->get([
            'id',
            'name',
            'user_name',
            'password',
            'school_id',
            'grade_id',
            'level_id',
            'section_id',
        ]);

        $zipPath = storage_path('app/tmp/qr_zip_'.Str::uuid().'.zip');
        if (! is_dir(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0775, true);
        }

        $zip = new ZipArchive;
        $opened = $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if ($opened !== true) {
            abort(500, 'Failed to create zip file.');
        }

        $filterLines = [];
        if ($schoolName) {
            $filterLines[] = 'School: '.$schoolName;
        }

        $zip->addFromString(
            'README.txt',
            "This ZIP contains SVG QR codes for student QR login links.\n"
            ."Folder structure: Grade/Section/Username StudentName.svg\n"
            .($schoolName ? ('Selected school: '.$schoolName."\n") : '')
            .(count($filterLines) ? ('Filters: '.implode(' | ', $filterLines)."\n") : '')
            .'Generated at: '.now()->toDateTimeString()."\n"
            ."Count: {$students->count()}\n"
        );

        foreach ($students as $student) {
            $url = URL::temporarySignedRoute(
                'student.qr-login',
                now()->addDays(30),
                ['student' => $student->id]
            );

            $lines = $this->buildStudentQrLabelLines($student);
            $svg = $this->generateLabeledQrSvg($url, $lines);

            $zipEntryPath = $this->buildStudentZipEntryPath($student);
            $zip->addFromString($zipEntryPath, $svg);
        }

        $zip->close();

        $downloadNameParts = ['student_qr_codes'];
        if ($schoolName) {
            $downloadNameParts[] = $this->safeFileName($schoolName);
        }
        $downloadNameParts[] = now()->format('Y-m-d_H-i');
        $downloadName = implode('_', $downloadNameParts).'.zip';

        return response()->download($zipPath, $downloadName)->deleteFileAfterSend(true);
    }

    protected function LoadStudents()
    {
        $query = Student::query()
            ->applyArchiveFilters($this->archiveStatus, $this->year)
            ->with('School', 'Section', 'Grade', 'assignedLevel');

        $query->when($this->school_id, function ($q) {
            $q->where('school_id', $this->school_id);
        });

        $query->when($this->grade_id, function ($q) {
            $q->where('grade_id', $this->grade_id);
        });

        $query->when($this->section, function ($q) {
            $q->withWhereHas('Section', function ($q) {
                $q->where('name', $this->section);
            });
        })
            ->when($this->level_id, function ($query) {
                $query->where('level_id', $this->level_id);
            });
        $search = trim((string) ($this->searchWord ?? ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('registration', 'LIKE', "%{$search}%")
                    ->orWhere('user_name', 'LIKE', "%{$search}%");
            });
        }

        return $query;
    }

    private function safeFileName(string $name): string
    {
        $name = trim($name);
        $name = str_replace(['\\', '/', ':', '*', '?', '"', '<', '>', '|'], '_', $name);
        $name = preg_replace('/\s+/', '_', $name);
        $name = preg_replace('/[^A-Za-z0-9_.-]/', '_', $name);

        return $name !== '' ? $name : 'qr_code';
    }

    /**
     * Safe folder/file segment for ZIP paths while preserving spaces and dashes.
     * Removes path separators and Windows-forbidden characters.
     */
    private function safeZipPathSegment(string $segment, string $fallback): string
    {
        $segment = trim($segment);

        // Remove control chars.
        $segment = preg_replace('/[\x00-\x1F\x7F]/u', '', $segment) ?? $segment;

        // Prevent ZIP path traversal / separators.
        $segment = str_replace(['\\', '/'], ' ', $segment);

        // Replace Windows-forbidden filename characters.
        $segment = str_replace([':', '*', '?', '"', '<', '>', '|'], ' ', $segment);

        // Collapse whitespace, keep spaces (no underscores).
        $segment = preg_replace('/\s+/u', ' ', $segment) ?? $segment;
        $segment = trim($segment);

        return $segment !== '' ? $segment : $fallback;
    }

    /**
     * ZIP entry path:
     *   GradeName/SectionName/UserName StudentName.svg
     */
    protected function buildStudentZipEntryPath(Student $student): string
    {
        $gradeName = trim((string) ($student->Grade?->name ?? ''));
        $gradeDir = $this->safeZipPathSegment($gradeName, 'Unknown Grade');

        $sectionName = '';
        if ($student->Section?->grade_id == $student->Grade?->id) {
            $sectionName = trim((string) ($student->Section?->name ?? ''));
        }
        $sectionDir = $this->safeZipPathSegment($sectionName, 'Unknown Section');

        $username = trim((string) ($student->user_name ?? ''));
        $studentName = trim((string) ($student->name ?? ''));
        $fileBaseRaw = trim(($username !== '' ? $username : 'unknown_user').' '.($studentName !== '' ? $studentName : 'student'));
        $fileBase = $this->safeZipPathSegment($fileBaseRaw, 'Student');

        return $gradeDir.'/'.$sectionDir.'/'.$fileBase.'.svg';
    }

    /**
     * Build 4 display lines for QR label:
     * - Student Name
     * - Username: {username} | Password: {password}
     * - Grade name | Level | Section Name
     * - School Name
     */
    protected function buildStudentQrLabelLines(Student $student): array
    {
        $studentName = trim((string) ($student->name ?? ''));
        $username = trim((string) ($student->user_name ?? ''));
        $password = trim((string) ($student->password ?? ''));

        $gradeName = trim((string) ($student->Grade?->name ?? ''));

        $levelName = trim((string) ($student->assignedLevel?->name ?? ''));
        $sectionName = '';
        if ($student->Section?->grade_id == $student->Grade?->id) {
            $sectionName = trim((string) ($student->Section?->name ?? ''));
        }

        $schoolName = trim((string) ($student->School?->name ?? ''));

        return [
            ($studentName !== '' ? $studentName : 'N/A'),
            'Username: '.($username !== '' ? $username : 'N/A'),
            'Password: '.($password !== '' ? $password : 'N/A'),
            ($gradeName !== '' ? $gradeName : 'N/A').' | '.($levelName !== '' ? $levelName : 'N/A').' | '.($sectionName !== '' ? $sectionName : 'N/A'),
            ($schoolName !== '' ? $schoolName : 'N/A'),
        ];
    }

    public function getStudentQrSvg(Student $student): string
    {
        $url = URL::temporarySignedRoute(
            'student.qr-login',
            now()->addDays(30),
            ['student' => $student->id]
        );

        return $this->generateLabeledQrSvg(
            $url,
            $this->buildStudentQrLabelLines($student)
        );
    }

    /**
     * Rough width of a string in Arial at a given size. There are no font
     * metrics available here, so the advance is approximated per character
     * class: capitals and digits are wide, lowercase middling, spaces and
     * punctuation narrow. Deliberately a shade generous, so a line judged to
     * fit really does.
     */
    protected function estimateTextWidth(string $text, float $fontSize, bool $bold = false): float
    {
        $units = 0.0;

        foreach (preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $char) {
            if (preg_match('/[A-Z0-9@#&]/u', $char)) {
                $units += 0.72;
            } elseif (preg_match('/[a-z]/u', $char)) {
                $units += 0.55;
            } elseif (preg_match('/[\s.,:;|!()\[\]\x27`-]/u', $char)) {
                $units += 0.30;
            } else {
                $units += 0.62;
            }
        }

        // bold Arial runs about 6% wider than regular
        return $units * $fontSize * ($bold ? 1.06 : 1.0);
    }

    /**
     * Break a line in two at a space, with both halves inside the width.
     * Returns null when no break achieves that.
     *
     * @return list<string>|null
     */
    protected function wrapLabelLine(string $text, float $maxWidth, float $fontSize, bool $bold = false): ?array
    {
        $words = preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if (count($words) < 2) {
            return null;
        }

        $best = null;
        $bestDifference = null;

        for ($split = 1; $split < count($words); $split++) {
            $first = implode(' ', array_slice($words, 0, $split));
            $second = implode(' ', array_slice($words, $split));

            $firstWidth = $this->estimateTextWidth($first, $fontSize, $bold);
            $secondWidth = $this->estimateTextWidth($second, $fontSize, $bold);

            if ($firstWidth > $maxWidth || $secondWidth > $maxWidth) {
                continue;
            }

            // the most even break reads best on a printed card
            $difference = abs($firstWidth - $secondWidth);

            if ($bestDifference === null || $difference < $bestDifference) {
                $best = [$first, $second];
                $bestDifference = $difference;
            }
        }

        return $best;
    }

    /** Last resort: shorten until it fits, marking that it was cut. */
    protected function shortenToWidth(string $text, float $maxWidth, float $fontSize, bool $bold = false): string
    {
        $ellipsis = "\u{2026}";

        while ($text !== '' && $this->estimateTextWidth($text.$ellipsis, $fontSize, $bold) > $maxWidth) {
            $text = mb_substr($text, 0, mb_strlen($text) - 1);
        }

        return rtrim($text).$ellipsis;
    }

    /**
     * Fit one label line to the card.
     *
     * Every line used to be drawn at a fixed 14px centred on a 300px card, so a
     * long name simply ran off both edges and printed cut in half; 8.7% of
     * students have a name too long for that. The line is shrunk first, then
     * broken over two lines, and only shortened when a name will not fit even
     * then.
     *
     * @return list<array{text: string, size: float, bold: bool}>
     */
    protected function fitLabelLine(string $text, float $maxWidth, float $maxSize = 14.0, float $minSize = 9.0, bool $bold = false): array
    {
        // one line, as large as fits, down to a size still comfortable in print
        for ($size = $maxSize; $size >= $minSize + 2; $size -= 0.5) {
            if ($this->estimateTextWidth($text, $size, $bold) <= $maxWidth) {
                return [['text' => $text, 'size' => $size, 'bold' => $bold]];
            }
        }

        // two lines, broken at a space
        for ($size = $maxSize - 1; $size >= $minSize; $size -= 0.5) {
            if ($wrapped = $this->wrapLabelLine($text, $maxWidth, $size, $bold)) {
                return array_map(fn ($part) => ['text' => $part, 'size' => $size, 'bold' => $bold], $wrapped);
            }
        }

        return [['text' => $this->shortenToWidth($text, $maxWidth, $minSize, $bold), 'size' => $minSize, 'bold' => $bold]];
    }

    /**
     * Generate an SVG that contains QR code + label lines.
     */
    protected function generateLabeledQrSvg(string $url, array $lines): string
    {
        $qrSize = 300;
        $qrSvg = QrCode::format('svg')
            ->size($qrSize)
            ->errorCorrection('H')
            ->margin(1)
            ->generate($url);

        // Strip XML header if present (so we can safely wrap in our own <svg>).
        $qrSvg = preg_replace('/^\s*<\?xml[^>]*>\s*/', '', (string) $qrSvg) ?? (string) $qrSvg;

        if (! preg_match('/<svg\b([^>]*)>(.*)<\/svg>/si', $qrSvg, $m)) {
            // Unexpected format; return raw QR svg.
            return (string) $qrSvg;
        }

        $attrs = (string) ($m[1] ?? '');
        $inner = (string) ($m[2] ?? '');

        $viewBox = "0 0 {$qrSize} {$qrSize}";
        if (preg_match('/viewBox\s*=\s*"([^"]+)"/i', $attrs, $vb)) {
            $viewBox = trim((string) $vb[1]);
        }

        $fontSize = 14;
        $lineGap = 6;
        $padding = 12;

        $cleanLines = [];
        foreach ($lines as $line) {
            $line = trim((string) $line);
            if ($line !== '') {
                $cleanLines[] = $line;
            }
        }

        // Fit each line to the card before measuring, so the block is as tall as
        // the text actually needs rather than assuming one line of one size.
        $maxTextWidth = $qrSize - (2 * $padding);

        $fittedLines = [];
        foreach ($cleanLines as $index => $line) {
            // the first line is the student's name, which the card prints in bold
            $bold = $index === 0;

            foreach ($this->fitLabelLine($line, $maxTextWidth, $fontSize, 9.0, $bold) as $fitted) {
                $fittedLines[] = $fitted;
            }
        }

        $textHeight = 0.0;
        foreach ($fittedLines as $index => $fitted) {
            $textHeight += $fitted['size'] + ($index > 0 ? $lineGap : 0);
        }

        $totalH = (int) ceil($qrSize + ($textHeight > 0 ? ($padding + $textHeight + $padding) : 0));

        $out = [];
        $out[] = '<svg xmlns="http://www.w3.org/2000/svg" width="'.$qrSize.'" height="'.$totalH.'" viewBox="0 0 '.$qrSize.' '.$totalH.'">';
        $out[] = '<rect x="0" y="0" width="100%" height="100%" fill="#ffffff"/>';

        // Render the QR at the top using a nested <svg> to keep its original viewBox.
        $out[] = '<svg x="0" y="0" width="'.$qrSize.'" height="'.$qrSize.'" viewBox="'.htmlspecialchars($viewBox, ENT_QUOTES | ENT_XML1).'" shape-rendering="crispEdges">';
        $out[] = $inner;
        $out[] = '</svg>';

        if ($fittedLines) {
            $y = $qrSize + $padding;
            $x = $qrSize / 2;

            foreach ($fittedLines as $index => $fitted) {
                $y += $fitted['size'] + ($index > 0 ? $lineGap : 0);

                $out[] = '<text x="'.$x.'" y="'.$y.'" font-family="Arial, sans-serif" font-size="'
                    .rtrim(rtrim(number_format($fitted['size'], 1, '.', ''), '0'), '.')
                    .'" text-anchor="middle" fill="#000000"'
                    .(($fitted['bold'] ?? false) ? ' font-weight="bold"' : '').'>'
                    .htmlspecialchars($fitted['text'], ENT_QUOTES | ENT_XML1)
                    .'</text>';
            }
        }

        $out[] = '</svg>';

        return implode("\n", $out);
    }

    public function exportExcel()
    {
        // Keep existing export signature but ensure it at least exports for the current global search.
        // (Filters are applied in the list and QR download; export can be upgraded similarly if needed.)
        return Excel::download(
            new StudentExport($this->LoadStudents()),
            'student_list_date_'.Carbon::now()->format('Y-m-d').'.xlsx'
        );
    }

    /**
     * @return array<string, string>
     */
    protected function sortableColumns(): array
    {
        return [
            'name' => 'students.name',
            'registration' => 'students.registration',
            'grade' => 'grades.number',
            'level' => 'levels.number',
            'section' => 'sections.name',
            'school' => 'schools.name',
        ];
    }

    protected function applySortJoins($query)
    {
        // schools, sections and grades all have a `name`, so the base columns
        // must be reselected explicitly or the join makes them ambiguous.
        $query->select('students.*');

        return match ($this->sortField) {
            'grade' => $query->leftJoin('grades', 'grades.id', '=', 'students.grade_id'),
            'level' => $query->leftJoin('levels', 'levels.id', '=', 'students.level_id'),
            'section' => $query->leftJoin('sections', 'sections.id', '=', 'students.section_id'),
            'school' => $query->leftJoin('schools', 'schools.id', '=', 'students.school_id'),
            default => $query,
        };
    }

    public function render()
    {
        $students = $this->applySorting($this->LoadStudents(), 'students.name', 'asc')->paginate(20);

        $schools = School::select('id', 'name')
            ->distinct()
            ->orderBy('name', 'asc')
            ->get();

        $grades = Grade::query()
            ->whereNotNull('number')
            ->orderBy('number')
            ->get(['id', 'name', 'number']);

        $sections = Section::select('name')
            ->when($this->school_id, fn ($q) => $q->where('school_id', $this->school_id))
            ->when($this->grade_id, fn ($q) => $q->where('grade_id', $this->grade_id))
            ->distinct()
            ->orderBy('name', 'asc')
            ->get()
            ->sortBy('name', SORT_NATURAL);

        $levels = Level::query()
            ->orderBy('number')
            ->get(['id', 'name', 'number']);

        return view('livewire.admin.students.all-students', [
            'students' => $students,
            'schools' => $schools,
            'grades' => $grades,
            'sections' => $sections,
            'levels' => $levels,
        ])->layout('layouts.base')->layoutData([
            'title' => 'Students',
            'pageTitle' => 'Students',
            'breadcrumb' => [
                'Dashboard' => route('admin.dashboard'),
                'Students' => '#',
            ],
        ]);
    }
}
