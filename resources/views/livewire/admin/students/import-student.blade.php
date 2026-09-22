<div>
    <div class="card">
        <div class="card-body">

            <div class="alert bg-soft-info">
                <h4>Message</h4>
                <p>Before uploading, please <a href="{{ route('download-sample') }}" class="alert-link">Download</a> the
                    sample file and verify
                    it against your CSV or Excel file. Ensure that the column names in your file match those in the
                    sample file. Each user registration number should be unique and compulsory.
                    <br>
                    When registering new student records, usernames and passwords are not mandatory. The system will
                    generate them automatically. However, if you prefer, you can set passwords of your own.
                </p>
            </div>

            <h6>Select CSV File To Import Students</h6>
            <div class="row justify-content-center align-items-center g-2">
                <div class="col-md-5">
                    <div class="mb-3">
                        <select wire:model.defer="school_id" class="form-control">
                            <option value="">Select School</option>
                            @foreach ($schools as $s)
                                <option value="{{ $s->id }}" {{ $school_id == $s->id ? 'selected' : '' }}>
                                    {{ $s->name }}</option>
                            @endforeach
                        </select>
                        @error('school_id')
                            <span class="ms-3 text-danger">

                                {{ str_replace('field', '', $message) }}
                            </span>
                        @enderror
                    </div>
                </div>
                <div class="col-md-5 position-reletive">
                    <div class="mb-3">
                        <input type="file" wire:model.defer="file" class="form-control">
                        @error('file')
                            <span class="ms-3 text-danger">

                                {{ str_replace('field', '', $message) }}
                            </span>
                        @enderror
                    </div>
                    <div wire:loading wire:target="file"
                        class="spinner-container position-absolute bottom-50 start-50 translate-middle"
                        style="z-index: 1;">
                        <div class="spinner-border text-success" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 mb-3">
                    <button class="btn btn-success w-100" type="button" wire:click.prevent="import">Import
                        Students</button>
                </div>
            </div>
        </div>
    </div>

    <x-admin.list-toolbar>
        <button wire:click.prevent="addStudents" class="btn btn-primary">
            <i class='bx bx-plus-circle'></i> Add To Record
        </button>
    </x-admin.list-toolbar>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-centered align-middle table-nowrap table-check"
                    style="margin-bottom: 70px; !important">
                    <thead>
                        <tr class="fw-semibold text-center align-middle">
                            <th>Name</th>
                            <th>Registration</th>
                            <th>Username</th>
                            <th>Password</th>
                            <th>School Name</th>
                            <th>Grade / Year</th>
                            <th>Section</th>
                            <th>Level</th>
                            <th>Nationality</th>
                            <th>Category</th>
                            <th>Gender</th>
                            @foreach (\App\Support\StudentDemographics::FLAGS as $label)
                                <th>{{ $label }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @if ($students)
                            @foreach ($students as $student)
                                <tr class="align-middle text-center">
                                    <td>{{ $student['name'] }}</td>
                                    <td>{{ $student['registration'] }}</td>
                                    <td>{{ $student['user_name'] }}</td>
                                    <td>{{ $student['password'] }}</td>
                                    <td>{{ $student['school'] }}</td>
                                    <td>
                                        @if ($student['_resolved_grade_name'] ?? null)
                                            {{ $student['_resolved_grade_name'] }}
                                            @if (($student['grade'] ?? '') !== $student['_resolved_grade_name'])
                                                <br /><small class="text-muted">{{ $student['grade'] }}</small>
                                            @endif
                                        @else
                                            {{ $student['grade'] }}
                                        @endif
                                    </td>
                                    <td>{{ $student['section'] }}</td>
                                    <td>
                                        @if (!empty($student['_resolution_errors']))
                                            @foreach ($student['_resolution_errors'] as $error)
                                                <span class="text-danger d-block">{{ $error }}</span>
                                            @endforeach
                                            @if ($student['level'])
                                                <small class="text-muted">{{ $student['level'] }}</small>
                                            @endif
                                        @elseif ($student['_resolved_level_name'] ?? null)
                                            {{ $student['_resolved_level_name'] }}
                                            @if ($student['level'] && $student['level'] !== $student['_resolved_level_name'])
                                                <br /><small class="text-muted">{{ $student['level'] }}</small>
                                            @endif
                                        @else
                                            <span class="text-danger">Unresolved</span>
                                        @endif
                                    </td>
                                    <td>{{ $student['nationality'] }}</td>
                                    <td>{{ $student['category'] }}</td>
                                    @php($demographics = $student['_demographics'] ?? ['values' => [], 'errors' => []])
                                    @foreach (['gender' => null] + \App\Support\StudentDemographics::FLAGS as $column => $unused)
                                        <td>
                                            @if (isset($demographics['errors'][$column]))
                                                {{-- Show what was typed, and why it will be rejected. --}}
                                                <span class="text-danger">{{ $student[$column] }}</span>
                                                <br /><small class="text-danger">Not recognised</small>
                                            @elseif ($column === 'gender')
                                                {{ \App\Support\StudentDemographics::genderLabel($demographics['values'][$column] ?? null) }}
                                            @else
                                                {{ \App\Support\StudentDemographics::flagLabel($demographics['values'][$column] ?? null) }}
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        @else
                            <tr class="text-center position-reletive" style="height: 100px;">
                                <td class="bg-white" colspan="14">

                                    <div wire:loading wire:target="import"
                                        class="spinner-container position-absolute bottom-50 start-50 translate-middle"
                                        style="z-index: 1;">
                                        <div class="spinner-border text-success" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endif

                    </tbody>
                </table>

            </div>
        </div>
    </div>
    <div wire:loading wire:target="addStudents" class="modal" tabindex="-1">
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
</div>
