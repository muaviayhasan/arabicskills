<div>
    <form wire:submit.prevent="addActivities">
        <div class="row justify-content-center align-items-center g-2">

            @forelse($activities as $i => $acti)
                <div class="col-md-6">
                    <div class="card shadow-sm h-100 border-0">
                        <div class="card-body d-flex gap-3">

                            <!-- Checkbox -->
                            <div class="pt-1">
                                <input type="checkbox" class="form-check-input"
                                    wire:model.defer="inputs.{{ $acti->id }}" wire:key="activity-{{ $acti->id }}">
                            </div>

                            <!-- Content -->
                            <div class="flex-grow-1">

                                <!-- Alert -->
                                @if ($acti->type !== 'writing')
                                    <div class="alert alert-warning py-2 px-3 mb-2 small">
                                        <strong>Type changed:</strong> {{ ucfirst($acti->type) }}
                                    </div>
                                @endif

                                <!-- Title + Action -->
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <h6 class="mb-0 text-truncate fw-semibold">
                                        {{ $acti->title }}
                                    </h6>

                                    <a href="{{ route('admin.edit-question-bank', ['activity_id' => $acti->id]) }}"
                                        class="btn btn-sm btn-outline-success">
                                        Edit
                                    </a>
                                </div>

                                <!-- Description -->
                                <p class="text-muted mb-0 small">
                                    {!! \Illuminate\Support\Str::limit(strip_tags($acti->activity), 100) !!}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

            @empty
                <div class="col-12">
                    <h6 class="alert alert-danger">
                        No Assessment found for {{ \App\Support\ExamLevelHelper::levelNamesLabel($exam->level_ids) }} ({{ $exam->Grade?->name ?? 'grade' }})
                    </h6>
                </div>
            @endforelse



            <div class="modal-footer">
                <a href="{{ route('admin.exams') }}" class="btn btn-danger">Cancel</a>
                @if ($activities)
                    <button type="submit" class="btn btn-primary">Save</button>
                @endif
            </div>
        </div>
    </form>

</div>
