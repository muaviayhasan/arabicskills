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
     * Generate an SVG that contains the QR code and the label lines, laid out as on the AIBT cards.
     *
     * The QR uses error correction M: the login link is long, and at H (as before) the code was 65 squares across,
     * small enough to be hard to see and scan on a printed card. M draws it 49 across, each square about a quarter
     * larger at the same size, and still recovers from 15% damage; nothing is printed over the code, so H's extra
     * redundancy bought nothing. The quiet margin is two squares instead of one, so cards printed side by side
     * still scan.
     */
    protected function generateLabeledQrSvg(string $url, array $lines): string
    {
        $qrSize = 300;
        $qrSvg = QrCode::format('svg')
            ->size($qrSize)
            ->errorCorrection('M')
            ->margin(2)
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

        $padding = 12;
        $maxWidth = $qrSize - ($padding * 2);

        $cleanLines = [];
        foreach ($lines as $line) {
            $line = trim((string) $line);
            if ($line !== '') {
                $cleanLines[] = $line;
            }
        }

        // The first line is the student's name: bold and large, wrapped onto a second line rather than shrunk or
        // cut off when it is long. A third line, at a slightly smaller size, only for the very longest names.
        $rows = [];
        foreach ($cleanLines as $i => $line) {
            $bold = $i === 0;
            [$size, $wrapped] = $bold
                ? $this->fitLabelText($line, $maxWidth, true, [[16, 1], [15, 2], [14, 2], [13, 3], [12, 3]])
                : $this->fitLabelText($line, $maxWidth, false, [[14, 1], [13, 1], [12, 2]]);

            foreach ($wrapped as $text) {
                $rows[] = ['text' => $text, 'size' => $size, 'bold' => $bold];
            }
        }

        $textHeight = array_sum(array_map(fn ($row) => $this->labelLineHeight($row['size']), $rows));

        // Reserve room for a three-line name so every card in a printed sheet has the same height, as on AIBT.
        $standardHeight = (3 * $this->labelLineHeight(13)) + ((count($cleanLines) - 1) * $this->labelLineHeight(14));
        $textHeight = count($rows) ? max($textHeight, $standardHeight) : 0;

        $totalH = (int) ceil($qrSize + ($textHeight ? ($padding + $textHeight + $padding) : 0));

        $out = [];
        $out[] = '<svg xmlns="http://www.w3.org/2000/svg" width="'.$qrSize.'" height="'.$totalH.'" viewBox="0 0 '.$qrSize.' '.$totalH.'">';
        $out[] = '<rect x="0" y="0" width="100%" height="100%" fill="#ffffff"/>';

        // Render the QR at the top using a nested <svg> to keep its original viewBox.
        $out[] = '<svg x="0" y="0" width="'.$qrSize.'" height="'.$qrSize.'" viewBox="'.htmlspecialchars($viewBox, ENT_QUOTES | ENT_XML1).'" shape-rendering="crispEdges">';
        $out[] = $inner;
        $out[] = '</svg>';

        $top = $qrSize + $padding;
        $x = $qrSize / 2;

        foreach ($rows as $row) {
            $lineHeight = $this->labelLineHeight($row['size']);
            $baseline = round($top + ($lineHeight + $row['size'] * 0.7) / 2, 1);

            // Last resort for a line that still does not fit: squeeze it to the card width instead of clipping it.
            $squeeze = $this->labelTextWidth($row['text'], $row['size'], $row['bold']) > $maxWidth
                ? ' textLength="'.$maxWidth.'" lengthAdjust="spacingAndGlyphs"'
                : '';

            $out[] = '<text x="'.$x.'" y="'.$baseline.'" font-family="Arial, sans-serif" font-size="'.$row['size'].'"'
                .($row['bold'] ? ' font-weight="bold"' : '').' text-anchor="middle" fill="#000000"'.$squeeze.'>'
                .htmlspecialchars($row['text'], ENT_QUOTES | ENT_XML1)
                .'</text>';

            $top += $lineHeight;
        }

        $out[] = '</svg>';

        return implode("\n", $out);
    }

    /**
     * Pick the largest font size whose word-wrapped text fits within the allowed number of lines.
     *
     * @param  array<int, array{0: int, 1: int}>  $attempts  [font size, max lines] pairs, tried in order
     * @return array{0: int, 1: array<int, string>}
     */
    protected function fitLabelText(string $text, float $maxWidth, bool $bold, array $attempts): array
    {
        $wrapped = [];

        foreach ($attempts as [$size, $maxLines]) {
            $wrapped = $this->wrapLabelText($text, $size, $maxWidth, $bold);

            if (count($wrapped) <= $maxLines) {
                return [$size, $wrapped];
            }
        }

        // Nothing fit: keep the last attempt's line count and put the overflow on the final line, which gets squeezed.
        [$size, $maxLines] = end($attempts);
        $kept = array_slice($wrapped, 0, $maxLines - 1);
        $kept[] = implode(' ', array_slice($wrapped, $maxLines - 1));

        return [$size, $kept];
    }

    /**
     * Break text between words so each line fits $maxWidth.
     *
     * @return array<int, string>
     */
    protected function wrapLabelText(string $text, int $size, float $maxWidth, bool $bold): array
    {
        $lines = [];
        $current = '';

        foreach (preg_split('/\s+/u', trim($text)) ?: [] as $word) {
            $candidate = $current === '' ? $word : $current.' '.$word;

            if ($current !== '' && $this->labelTextWidth($candidate, $size, $bold) > $maxWidth) {
                $lines[] = $current;
                $current = $word;
            } else {
                $current = $candidate;
            }
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines;
    }

    protected function labelLineHeight(int $size): float
    {
        return round($size * 1.35, 1);
    }

    /**
     * Estimate rendered width from Arial's character widths, since SVG text cannot be measured server-side.
     */
    protected function labelTextWidth(string $text, int $size, bool $bold): float
    {
        $units = 0;
        foreach (mb_str_split($text) as $char) {
            $units += self::ARIAL_WIDTHS[$char] ?? (ctype_digit($char) ? 556 : 600);
        }

        // Bold Arial runs about 10% wider; the extra 5% is a safety margin for font substitution.
        return $units / 1000 * $size * ($bold ? 1.1 : 1.0) * 1.05;
    }

    /**
     * Arial advance widths in 1/1000 em.
     */
    private const ARIAL_WIDTHS = [
        ' ' => 278, '.' => 278, ',' => 278, ':' => 278, ';' => 278, '!' => 278, '|' => 260, "'" => 191,
        '-' => 333, '(' => 333, ')' => 333, '/' => 278, '&' => 667, '_' => 556, '@' => 1015,
        'A' => 667, 'B' => 667, 'C' => 722, 'D' => 722, 'E' => 667, 'F' => 611, 'G' => 778, 'H' => 722, 'I' => 278,
        'J' => 500, 'K' => 667, 'L' => 556, 'M' => 833, 'N' => 722, 'O' => 778, 'P' => 667, 'Q' => 778, 'R' => 722,
        'S' => 667, 'T' => 611, 'U' => 722, 'V' => 667, 'W' => 944, 'X' => 667, 'Y' => 667, 'Z' => 611,
        'a' => 556, 'b' => 556, 'c' => 500, 'd' => 556, 'e' => 556, 'f' => 278, 'g' => 556, 'h' => 556, 'i' => 222,
        'j' => 222, 'k' => 500, 'l' => 222, 'm' => 833, 'n' => 556, 'o' => 556, 'p' => 556, 'q' => 556, 'r' => 333,
        's' => 500, 't' => 278, 'u' => 556, 'v' => 500, 'w' => 722, 'x' => 500, 'y' => 500, 'z' => 500,
    ];

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
