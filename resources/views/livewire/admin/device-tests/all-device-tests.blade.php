<div>
    <x-admin.list-toolbar>
        <input type="text" class="form-control" wire:model.live.debounce.300ms="search"
            placeholder="Search by student name or ID..." style="max-width: 320px;">
    </x-admin.list-toolbar>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Student</th>
                            <th>Registration ID</th>
                            <th>Overall Status</th>
                            <th>Device OS</th>
                            <th>Tested At</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tests as $test)
                            <tr>
                                <td>{{ $loop->iteration + $tests->firstItem() - 1 }}</td>
                                <td>
                                    @if($test->student)
                                        <div class="d-flex align-items-center">
                                            @if($test->student->profile_image)
                                                <img src="{{ read_image($test->student->profile_image) }}" class="rounded-circle me-2" style="width: 35px; height: 35px; object-fit: cover;">
                                            @else
                                                <div class="bg-secondary text-white rounded-circle d-flex justify-content-center align-items-center me-2" style="width: 35px; height: 35px;">
                                                    {{ substr($test->student->name, 0, 1) }}
                                                </div>
                                            @endif
                                            <span class="fw-bold">{{ $test->student->name }}</span>
                                        </div>
                                    @else
                                        <span class="text-muted">Unknown</span>
                                    @endif
                                </td>
                                <td>{{ $test->student ? $test->student->registration : 'N/A' }}</td>
                                <td>
                                    @if($test->overall_status === 'passed')
                                        <span class="badge bg-success px-2 py-1"><i class="fa-solid fa-check"></i> Passed</span>
                                    @else
                                        <span class="badge bg-danger px-2 py-1"><i class="fa-solid fa-times"></i> Failed</span>
                                    @endif
                                </td>
                                <td>{{ $test->device_info['os'] ?? 'Unknown' }}</td>
                                <td>{{ $test->created_at->format('M d, Y h:i A') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('admin.device-tests.details', $test->id) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fa-solid fa-eye"></i> View Details
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="fa-solid fa-folder-open fs-3 mb-2"></i>
                                    <p class="mb-0">No device tests found.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($tests->hasPages())
                <div class="p-3 border-top">
                    {{ $tests->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
