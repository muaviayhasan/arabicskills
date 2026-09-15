<div>
    <!-- Search Filters -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-12 col-md-6 col-lg-3">
                            <label class="form-label">School</label>
                            <select wire:model="school_id" class="form-select bg-light border-light rounded">
                                <option value="">All Schools</option>
                                @foreach ($schools as $school)
                                    <option value="{{ $school->id }}">{{ $school->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-6 col-lg-4">
                            <label class="form-label">Grade</label>
                            <select wire:model="grade_id" class="form-select bg-light border-light rounded">
                                <option value="">All Grades</option>
                                @foreach ($grades as $grade)
                                    <option value="{{ $grade->id }}">{{ $grade->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-6 col-lg-2">
                            <label class="form-label">Section</label>
                            <select wire:model="section" class="form-select bg-light border-light rounded">
                                <option value="">All Sections</option>
                                @foreach ($sections as $sec)
                                    <option value="{{ $sec->name }}">{{ $sec->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-6 col-lg-3">
                            <label class="form-label">Level</label>
                            <select wire:model="level_id" class="form-select bg-light border-light rounded">
                                <option value="">All Levels</option>
                                @foreach ($levels as $lvl)
                                    <option value="{{ $lvl->id }}">{{ $lvl->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-6 col-lg-2">
                            <label class="form-label">Academic Year</label>
                            <x-student-academic-year-select wire:model="year" />
                        </div>
                        <div class="col-12 col-md-6 col-lg-2">
                            <label class="form-label">Status</label>
                            <select wire:model="archiveStatus" class="form-select bg-light border-light rounded">
                                <option value="active">Active</option>
                                <option value="archived">Archived</option>
                                <option value="all">All</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-12 col-lg-12">
                            <label class="form-label">Search</label>
                            <input type="text" wire:model="searchWord"
                                class="form-control bg-light border-light rounded"
                                placeholder="Name, Registration, Username...">
                        </div>
                        <div class="col-12">
                            <div class="d-flex gap-2">
                                <button type="button" wire:click="manageSearch" class="btn btn-primary"
                                    wire:loading.attr="disabled">
                                    <span wire:loading.remove wire:target="manageSearch">
                                        <i class="bx bx-search-alt align-middle"></i> Search
                                    </span>
                                    <span wire:loading wire:target="manageSearch">
                                        <span class="spinner-border spinner-border-sm align-middle" role="status"
                                            aria-hidden="true"></span>
                                        Searching...
                                    </span>
                                </button>
                                <button type="button" wire:click="resetFilters" class="btn btn-secondary"
                                    wire:loading.attr="disabled">
                                    <span wire:loading.remove wire:target="resetFilters">
                                        <i class="bx bx-reset align-middle"></i> Reset
                                    </span>
                                    <span wire:loading wire:target="resetFilters">
                                        <span class="spinner-border spinner-border-sm align-middle" role="status"
                                            aria-hidden="true"></span>
                                        Resetting...
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-admin.list-toolbar>
        @if (getPermissions('students', 'delete'))
            <button type="button" class="btn btn-danger" wire:click.prevent="bulkArchiveStudents"
                wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="bulkArchiveStudents,bulkArchiveStudentsConfirm">
                    <i class='bx bx-archive align-middle'></i> Bulk Archive
                </span>
                <span wire:loading wire:target="bulkArchiveStudents,bulkArchiveStudentsConfirm">
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    Archiving...
                </span>
            </button>
            @if ($archiveStatus !== 'active')
                <button type="button" class="btn btn-secondary" wire:click.prevent="bulkRestoreStudents"
                    wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="bulkRestoreStudents,bulkRestoreStudentsConfirm">
                        <i class='bx bx-undo align-middle'></i> Bulk Restore
                    </span>
                    <span wire:loading wire:target="bulkRestoreStudents,bulkRestoreStudentsConfirm">
                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                        Restoring...
                    </span>
                </button>
            @endif
        @endif
        <button class="btn btn-success" type="button" wire:click.prevent="exportExcel">
            <i class='bx bx-spreadsheet align-middle'></i> Export
        </button>
        <button type="button" class="btn btn-info" wire:click="downloadQrsZip" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="downloadQrsZip">
                <i class='bx bx-download align-middle'></i> Download QRs
            </span>
            <span wire:loading wire:target="downloadQrsZip">
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                Preparing...
            </span>
        </button>
        <a href="{{ route('admin.import-students') }}" class="btn btn-secondary">
            <i class='bx bx-spreadsheet align-middle'></i> Import
        </a>
        <a href="{{ route('admin.add-student') }}" class="btn btn-primary">
            <i class='bx bx-plus-circle align-middle'></i> Add
        </a>
    </x-admin.list-toolbar>

    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-centered align-middle table-nowrap table-check"
                            style="margin-bottom: 70px; !important">
                            <thead>
                                <tr class="fw-semibold text-center">
                                    <th>#</th>
                                    <x-admin.sortable-header field="name" label="Name"
                                            :current="$sortField" :direction="$sortDirection" />
                                    <x-admin.sortable-header field="registration" label="Reg/Username"
                                            :current="$sortField" :direction="$sortDirection" />
                                    <x-admin.sortable-header field="grade" label="Grade"
                                            :current="$sortField" :direction="$sortDirection" />
                                    <x-admin.sortable-header field="level" label="Level"
                                            :current="$sortField" :direction="$sortDirection" />
                                    <x-admin.sortable-header field="section" label="Section"
                                            :current="$sortField" :direction="$sortDirection" />
                                    <x-admin.sortable-header field="school" label="School"
                                            :current="$sortField" :direction="$sortDirection" />
                                    <th>Password</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>

                                @forelse($students as $student)
                                    <tr class="text-center">
                                        <td>{{ $loop->iteration + $students->firstItem() - 1 }}</td>

                                        <td class="fw-semibold">

                                            <span type="button" class="mb-1" title="QR Login" data-bs-toggle="modal"
                                                data-bs-target="#loginQrModal"
                                                data-url="{{ URL::temporarySignedRoute('student.qr-login', now()->addDays(30), ['student' => $student->id]) }}"
                                                data-filename="{{ $student->user_name }}"
                                                data-student-name="{{ Str::slug($student->name) }}"
                                                data-student-title="{{ $student->name }}"
                                                data-username="{{ $student->user_name }}" data-password="{{ $student->password }}"
                                                data-level="{{ $student->assignedLevel?->name }}"
                                                data-grade-name="{{ $student->Grade?->name }}"
                                                data-section-name="{{ $student->Section?->name }}"
                                                data-school-name="{{ $student->School?->name }}">
                                                <i class="border border-3 border-dark fa fa-qrcode p-1"></i>
                                            </span>


                                            <br />

                                            {{ $student->name }}
                                            @if ($student->trashed())
                                                <span class="badge bg-secondary ms-1">Archived</span>
                                            @endif
                                            <br />
                                            <small class="text-muted">
                                                Created: {{ date('d M, Y H:i', strtotime($student->created_at)) }}
                                                <br />
                                                Updated: {{ date('d M, Y H:i', strtotime($student->updated_at)) }}
                                            </small>
                                        </td>
                                        <td>
                                            {{ $student->registration }} <br>
                                            <span class="text-success">{{ $student->user_name }}</span>
                                        </td>

                                        <td>
                                            {{ $student->Grade?->name ?? '' }}
                                        </td>
                                        <td>
                                            {{ $student->assignedLevel?->name ?? 'N/A' }}
                                        </td>
                                        <td>
                                            {{ $student->Section?->name ?? '' }}
                                        </td>

                                        <td>
                                            {{ $student->School?->name ?? '' }}
                                        </td>
                                        <td>
                                            {{ $student->password }}
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <button type="button"
                                                    class="btn btn-sm btn-danger border-0 shadow-none p-2"
                                                    data-bs-toggle="dropdown"
                                                    aria-label="Actions for {{ $student->name }}">
                                                    <i class='bx bx-dots-vertical-rounded fs-5 align-middle'></i>
                                                </button>
                                                <div class="dropdown-menu">
                                                    @if (getPermissions('students', 'edit'))
                                                        <a href="{{ route('admin.edit-student', ['student' => $student->id]) }}"
                                                            class="dropdown-item text-center">Edit</a>
                                                    @endif
                                                    @if (!$student->trashed() && getPermissions('students', 'delete'))
                                                        <a class="dropdown-item text-center"
                                                            wire:click.prevent="archiveStudent({{ $student->id }})"
                                                            href="#">Archive</a>
                                                    @endif
                                                    @if ($student->trashed() && getPermissions('students', 'delete'))
                                                        <a class="dropdown-item text-center"
                                                            wire:click.prevent="restoreStudent({{ $student->id }})"
                                                            href="#">Restore</a>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="text-center">
                                        <td class="bg-white" colspan="12">

                                            <img src="{{ asset('assets/images/empty.png') }}" alt="Empty List Image"
                                                width="25%">
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="p-3">
                        <div class="float-end">
                            {{ $students->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- Modal -->
    <div wire:ignore.self class="modal" id="imageModal" tabindex="-1" role="dialog" aria-labelledby="imageModalLabel"
        aria-hidden="true">
        <button type="button" class="btn text-white position-absolute m-3 fs-2" data-bs-dismiss="modal"
            aria-label="Close" style="top: 20px; right:30px;"><i class='bx bx-x-circle'></i></button>
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content" style="border: none; background: none;">
                <div class="modal-body p-0">
                    <!-- Image goes here -->
                    <img src="" alt="Image" id="modalImage" width="400px">
                </div>
            </div>
            <!-- Close button hidden on small screens -->
        </div>
    </div>

    <div wire:loading wire:target="exportExcel" class="modal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center">
                    <div class="spinner-border text-primary" role="status" style="width: 4rem; height: 4rem;">
                    </div>
                    <p class="mt-2">Please wait...</p>
                </div>
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="loginQrModal" data-bs-backdrop="static" data-bs-keyboard="false"
        tabindex="-1" aria-labelledby="loginQrModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="loginQrModalLabel">Username</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex flex-column align-items-center">
                        <canvas id="loginQrCanvas" style="max-width: 100%;"></canvas>
                        <div class="text-center mt-2 small" id="loginQrInfo">
                            <div id="loginQrLine1"></div>
                            <div id="loginQrLine2"></div>
                            <div id="loginQrLine3"></div>
                            <div id="loginQrLine4"></div>
                            <div id="loginQrLine5"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" target="_blank" type="button" class="btn btn-success loginBtn">Login</a>
                    <button type="button" class="btn btn-primary qrDownload">Download</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/qrious@4.0.2/dist/qrious.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {

            let qr;
            let currentFilename = 'qr-code';

            // When modal is about to be shown
            const loginQrModal = document.getElementById('loginQrModal');

            loginQrModal.addEventListener('show.bs.modal', function (event) {
                const trigger = event.relatedTarget;
                console.log(trigger);

                const url = trigger.getAttribute('data-url');
                const filename = trigger.getAttribute('data-filename') || 'qr-code';

                const studentTitle = trigger.getAttribute('data-student-title') || '';
                const username = trigger.getAttribute('data-username') || filename;
                const password = trigger.getAttribute('data-password') || 'N/A';
                const gradeName = trigger.getAttribute('data-grade-name') || 'N/A';
                const levelName = trigger.getAttribute('data-level') || 'N/A';
                const sectionName = trigger.getAttribute('data-section-name') || 'N/A';
                const schoolName = trigger.getAttribute('data-school-name') || 'N/A';

                document.getElementById('loginQrModalLabel').textContent = username + (studentTitle ? (
                    " - " + studentTitle) : '');

                const canvas = document.getElementById('loginQrCanvas');

                currentFilename = filename + '-' + trigger.getAttribute('data-student-name');

                // Populate requested label lines (shown under QR and used in downloaded image)
                const line1 = studentTitle ? studentTitle : 'N/A';
                const line2 = 'Username: ' + (username ? username : 'N/A');
                const line3 = 'Password: ' + (password ? password : 'N/A');
                const line4 = (gradeName ? gradeName : 'N/A') + ' | ' + (levelName ? levelName : 'N/A') + ' | ' + (sectionName ? sectionName : 'N/A');
                const line5 = schoolName ? schoolName : 'N/A';

                document.getElementById('loginQrLine1').textContent = line1;
                document.getElementById('loginQrLine2').textContent = line2;
                document.getElementById('loginQrLine3').textContent = line3;
                document.getElementById('loginQrLine4').textContent = line4;
                document.getElementById('loginQrLine5').textContent = line5;
                document.querySelector('.loginBtn').href = url;

                // Destroy previous QR if exists
                if (qr) {
                    qr.value = '';
                }

                qr = new QRious({
                    element: canvas,
                    value: url,
                    size: 300,
                    level: 'H'
                });
            });

            // Download button
            document.querySelector('.qrDownload').addEventListener('click', function () {
                const canvas = document.getElementById('loginQrCanvas');
                const link = document.createElement('a');

                // Create a labeled PNG (QR + 3 lines of text) like the server ZIP export.
                const lines = [
                    document.getElementById('loginQrLine1')?.textContent || '',
                    document.getElementById('loginQrLine2')?.textContent || '',
                    document.getElementById('loginQrLine3')?.textContent || '',
                    document.getElementById('loginQrLine4')?.textContent || '',
                    document.getElementById('loginQrLine5')?.textContent || '',
                ].filter(Boolean);

                const padding = 12;
                const lineGap = 6;
                const fontSize = 14;

                const out = document.createElement('canvas');
                const ctx = out.getContext('2d');
                if (!ctx) {
                    link.href = canvas.toDataURL('image/png');
                } else {
                    ctx.font = `${fontSize}px Arial`;
                    const textHeight = lines.length ? (lines.length * fontSize) + ((lines.length - 1) *
                        lineGap) : 0;
                    const extraH = lines.length ? (padding + textHeight) : 0;

                    out.width = canvas.width + (padding * 2);
                    out.height = canvas.height + (padding * 2) + extraH;

                    // white background
                    ctx.fillStyle = '#ffffff';
                    ctx.fillRect(0, 0, out.width, out.height);

                    // QR
                    ctx.drawImage(canvas, padding, padding);

                    // Text
                    if (lines.length) {
                        ctx.fillStyle = '#000000';
                        ctx.textBaseline = 'top';

                        let y = padding + canvas.height + padding;
                        for (const line of lines) {
                            const w = ctx.measureText(line).width;
                            const x = Math.max(0, (out.width - w) / 2);
                            ctx.fillText(line, x, y);
                            y += fontSize + lineGap;
                        }
                    }

                    link.href = out.toDataURL('image/png');
                }
                link.download = currentFilename + '.png';

                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            });

        });
    </script>


</div>