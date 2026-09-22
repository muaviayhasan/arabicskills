<div>
    {{-- Filters --}}
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-12 col-md-3">
                            <label class="form-label">School</label>
                            <select wire:model.live="school_id" class="form-control">
                                <option value="">All Schools</option>
                                @foreach ($schools as $school)
                                    <option value="{{ $school->id }}">{{ $school->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-2">
                            <label class="form-label">Academic Year</label>
                            <select wire:model.live="academic_year" class="form-control">
                                <option value="">All Years</option>
                                @foreach ($years as $year)
                                    <option value="{{ $year }}">{{ $year }} - {{ $year + 1 }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-2">
                            <label class="form-label">Round</label>
                            <select wire:model.live="round" class="form-control">
                                <option value="">All Rounds</option>
                                @foreach ($rounds as $number => $label)
                                    <option value="{{ $number }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label">Search</label>
                            <input wire:model.live.debounce.400ms="searchWord" type="text"
                                class="form-control bg-light border-light rounded" placeholder="Name or Student ID...">
                        </div>
                        <div class="col-12 col-md-2">
                            <label class="form-label d-none d-md-block" aria-hidden="true">&nbsp;</label>
                            <button type="button" wire:click="resetFilters" class="btn btn-secondary w-100">
                                <i class="bx bx-reset align-middle"></i> Reset
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-admin.list-toolbar>
        @if (getPermissions('results', 'add'))
            <a href="{{ route('admin.import-assessment-marks') }}" class="btn btn-primary">
                <i class='bx bx-upload align-middle'></i> Import Marks
            </a>
        @endif
    </x-admin.list-toolbar>

    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-centered align-middle table-nowrap">
                            <thead>
                                <tr class="fw-semibold text-center">
                                    <x-admin.sortable-header field="student" label="Student"
                                        :current="$sortField" :direction="$sortDirection" />
                                    <x-admin.sortable-header field="grade" label="Grade"
                                        :current="$sortField" :direction="$sortDirection" />
                                    <th>Details</th>
                                    <x-admin.sortable-header field="round" label="Round"
                                        :current="$sortField" :direction="$sortDirection" />
                                    @foreach (\App\Support\MarkRanges::SKILLS as $skill => $label)
                                        <th>{{ $label }}</th>
                                    @endforeach
                                    <x-admin.sortable-header field="total" label="Total"
                                        :current="$sortField" :direction="$sortDirection" />
                                    <th>Overall</th>
                                    <th>Expectation</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($marks as $mark)
                                    <tr class="text-center">
                                        <td class="text-start">
                                            <span class="fw-semibold">{{ $mark->Student?->name }}</span>
                                            <br /><small class="text-muted">{{ $mark->Student?->registration }}</small>
                                        </td>
                                        <td>
                                            {{ $mark->Student?->Grade?->name }}
                                            @if ($mark->Student?->Section?->name)
                                                <br /><small class="text-muted">{{ $mark->Student->Section->name }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            {{-- Pulled from the student record, as the client asked --}}
                                            <small class="d-block">{{ $mark->Student?->assignedLevel?->name }}</small>
                                            @php($details = $mark->Student ? \App\Support\StudentDemographics::summary($mark->Student) : '')
                                            @if ($details !== '')
                                                <small class="d-block text-primary">{{ $details }}</small>
                                            @endif
                                        </td>
                                        <td>{{ \App\Support\MarkRanges::ROUNDS[$mark->round] ?? $mark->round }}</td>

                                        @foreach (array_keys(\App\Support\MarkRanges::SKILLS) as $skill)
                                            <td>
                                                <div>{{ \App\Support\MarkRanges::displayMark($mark->{$skill}, \App\Support\MarkRanges::SKILL) }}</div>
                                                <x-admin.judgement-badge :judgement="$mark->judgementFor($skill)" small />
                                            </td>
                                        @endforeach

                                        <td class="fw-semibold">
                                            {{ \App\Support\MarkRanges::displayMark($mark->total, \App\Support\MarkRanges::TOTAL) }}
                                        </td>
                                        <td><x-admin.judgement-badge :judgement="$mark->overallJudgement()" /></td>
                                        <td>
                                            <x-admin.judgement-badge :judgement="$mark->expectation()" small />
                                            <small class="d-block text-muted">next round</small>
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="text-center">
                                        <td class="bg-white" colspan="12">
                                            <img src="{{ asset('assets/images/empty.png') }}" alt="Empty List Image"
                                                width="20%">
                                            <p class="text-muted">No marks imported yet for these filters.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="p-3">
                        <div class="float-end">
                            {{ $marks->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
