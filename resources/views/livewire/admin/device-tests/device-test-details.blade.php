<div>
    <div class="row">
        <div class="col-lg-12 mx-auto">

            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                    <h5 class="mb-0 fw-bold">
                        <i class="fa-solid fa-laptop-file text-primary me-2"></i>
                        Test Details for {{ $test->student->name ?? 'Unknown Student' }}
                        ({{ $test->student->registration ?? 'N/A' }})
                    </h5>
                    <a href="{{ route('admin.device-tests') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="fa-solid fa-arrow-left"></i> Back to List
                    </a>
                </div>
                <div class="card-body">
                    <h5 class="fw-bold mb-3 border-bottom pb-2">
                        Overall Status:
                        @if($test->overall_status === 'passed')
                            <span class="badge bg-success fs-6">Ready / Passed</span>
                        @else
                            <span class="badge bg-danger fs-6">Needs Attention / Failed</span>
                        @endif
                    </h5>
                    <p class="mb-0"><strong>Tested on:</strong> {{ $test->created_at->format('M d, Y h:i A') }}</p>
                </div>
            </div>

            <div class="card mb-4 shadow-sm border-0">
                <div class="card-header bg-light border-bottom">
                    <h5 class="mb-0 text-dark"><i class="fa-solid fa-mobile-screen me-2 text-primary"></i> Device
                        Information</h5>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        @foreach($test->device_info as $key => $value)
                            <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                                <strong class="text-secondary">{{ ucwords(str_replace('_', ' ', $key)) }}</strong>
                                <span>
                                    @if(is_bool($value))
                                        @if($value)
                                            <span class="badge bg-success">Yes</span>
                                        @else
                                            <span class="badge bg-danger">No</span>
                                        @endif
                                    @else
                                        <span class="fw-bold">{{ $value }}</span>
                                    @endif
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-light border-bottom">
                    <h5 class="mb-0 text-dark"><i class="fa-solid fa-list-check me-2 text-primary"></i> Test Results
                        Breakdown</h5>
                </div>
                <div class="card-body">
                    @foreach($test->test_results as $i => $result)
                        @php
                            $qType = $result['type'];
                            $fakeQ = $questions[$result['id']] ?? null;
                        @endphp

                        <div class="mb-4 border border-light bg-light p-4 rounded position-relative shadow-sm">
                            <div class="position-absolute top-0 end-0 m-3">
                                @if($result['status'] === 'passed')
                                    <span class="badge bg-success fs-6"><i class="fa-solid fa-check"></i> Passed</span>
                                @else
                                    <span class="badge bg-danger fs-6"><i class="fa-solid fa-times"></i> Failed</span>
                                @endif
                            </div>

                            <h5 class="text-primary border-bottom border-secondary pb-2 mb-3">
                                <i class="fa-solid fa-circle-question me-1 fs-6"></i>
                                Test #{{ $i + 1 }} : {{ ucfirst(str_replace('-', ' ', $qType)) }}
                            </h5>

                            @if($fakeQ)
                                <div class="text-dark mb-4 bg-white p-3 rounded border">
                                    <strong class="text-secondary d-block mb-2">Question Prompt:</strong>
                                    <div class="fs-6">{!! $fakeQ['question'] !!}</div>
                                    @if($qType === 'MCQs' && !empty($fakeQ['options']))
                                        <div class="row mt-3">
                                            @foreach($fakeQ['options'] as $key => $opt)
                                                <div class="col-md-3 mb-2">
                                                    <div class="p-2 border rounded text-center bg-light">({{ $key }}) {{ $opt }}</div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endif

                            <div>
                                @if(empty($result['answer']) || $result['answer'] === '{}' || $result['answer'] === '[]')
                                    <div class="alert alert-danger text-center mb-0">
                                        <i class="fa-solid fa-triangle-exclamation me-2"></i> Student did not attempt this
                                        question or answer was not recorded.
                                    </div>
                                @else
                                    <div
                                        class="rounded form-control py-3 {{ $result['status'] === 'passed' ? 'alert-success border-success' : 'alert-danger border-danger' }}">
                                        <div class="text-secondary mb-2 fw-bold"><i class="fa-solid fa-reply me-2"></i>
                                            Student's Answer: </div>

                                        @if(in_array($qType, ['writing', 'writing-answer']) && is_string($result['answer']) && str_contains($result['answer'], 'a:'))
                                            {{-- Fallback if any old tests have serialized image arrays --}}
                                            <div class="mt-2 row">
                                                @php
                                                    $images = unserialize($result['answer']);
                                                @endphp
                                                @foreach($images as $img)
                                                    <div class="col-md-4">
                                                        <img src="{{ read_image($img) }}" alt="Writing Answer"
                                                            class="img-fluid rounded border">
                                                    </div>
                                                @endforeach
                                            </div>
                                        @elseif(in_array($qType, ['rearrange', 'match_words']))
                                            @php
                                                $ansArray = json_decode($result['answer'], true) ?? [];
                                            @endphp
                                            <div class="mt-2">
                                                @foreach($ansArray as $key => $val)
                                                    <span
                                                        class="p-2 px-3 border border-dark bg-white rounded shadow-sm me-2 d-inline-block mb-2 fw-bold">
                                                        @if(is_string($key) && !is_numeric($key)) <span
                                                        class="text-primary">{{ $key }}</span> = @endif {{ $val }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @elseif($qType === 'match')
                                            @php
                                                $ansArray = json_decode($result['answer'], true) ?? [];
                                            @endphp
                                            <div class="row mt-2">
                                                @foreach($ansArray as $key => $val)
                                                    @if($fakeQ['isImgs'])
                                                        <div class="col-md-3 text-center border border-primary corder-2 rounded p-2 m-2">
                                                            <h6 class="border-bottom pb-1">
                                                                <img src="{{ asset('assets/images/' . trim($key)) }}" alt=""
                                                                    class="img-fluid" style="height: 100px;">
                                                            </h6>
                                                            <h6 class="pt-0">
                                                                <img src="{{ asset('assets/images/' . trim($val)) }}" alt=""
                                                                    class="img-fluid" style="height: 100px;">
                                                            </h6>
                                                        </div>
                                                    @else
                                                        <div class="col-md-3 text-center border border-primary rounded p-2 m-2">
                                                            <h6 class="border-bottom pb-1">{{ $key }}</h6>
                                                            <h6 class="pt-1">{{ $val }}</h6>
                                                        </div>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="p-2 bg-white rounded border d-inline-block fs-6 fw-bold text-dark">
                                                {{ is_array($result['answer']) ? json_encode($result['answer']) : $result['answer'] }}
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>

                        </div>
                    @endforeach
                </div>
            </div>

        </div>
    </div>
</div>