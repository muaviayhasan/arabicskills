<div>
    <x-std-header :title="'Device Test Results <span>نتائج اختبار الجهاز</span>'" />

    <section class="mt-5 mb-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 mx-auto">

                    <div class="card mb-4 shadow-sm">
                        <div class="card-body">
                            <h4 class="fw-bold mb-4">
                                Overall Status:
                                @if($test->overall_status === 'passed')
                                    <span class="badge bg-success fs-5">Ready / Passed</span>
                                @else
                                    <span class="badge bg-danger fs-5">Needs Attention / Failed</span>
                                @endif
                            </h4>
                            <p>Tested on: {{ $test->created_at->format('M d, Y h:i A') }}</p>
                        </div>
                    </div>

                    <div class="card mb-4 shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0 text-white">Device Information</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                @foreach($test->device_info as $key => $value)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <strong>{{ ucwords(str_replace('_', ' ', $key)) }}</strong>
                                        <span>
                                            @if(is_bool($value))
                                                @if($value)
                                                    <span class="badge bg-success">Yes</span>
                                                @else
                                                    <span class="badge bg-danger">No</span>
                                                @endif
                                            @else
                                                {{ $value }}
                                            @endif
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>

                    <div class="card mb-4 shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0 text-white">Submitted Answers Review</h5>
                        </div>
                        <div class="card-body">
                            @foreach($test->test_results as $i => $result)
                                @php
                                    $qType = $result['type'];
                                    $fakeQ = $questions[$result['id']] ?? null;
                                @endphp

                                <div class="mb-4 border border-primary p-3 rounded position-relative">
                                    <div class="position-absolute top-0 end-0 m-3">
                                        @if($result['status'] === 'passed')
                                            <span class="badge bg-success fs-6"><i class="fa-solid fa-check"></i> Passed</span>
                                        @else
                                            <span class="badge bg-danger fs-6"><i class="fa-solid fa-times"></i> Failed</span>
                                        @endif
                                    </div>

                                    <h5 class="text-primary border-bottom pb-2">Test #{{ $i + 1 }} :
                                        {{ ucfirst(str_replace('-', ' ', $qType)) }}</h5>

                                    @if($fakeQ)
                                        <div class="mt-3 text-dark mb-3">
                                            <strong>Question: </strong> {!! $fakeQ['question'] !!}
                                            @if($qType === 'MCQs' && !empty($fakeQ['options']))
                                                <div class="row mt-2">
                                                    @foreach($fakeQ['options'] as $key => $opt)
                                                        <div class="col-md-3">({{ $key }}) {{ $opt }}</div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    @endif

                                    <div class="mt-3">
                                        @if(empty($result['answer']) || $result['answer'] === '{}' || $result['answer'] === '[]')
                                            <div class="alert alert-danger text-center">
                                                Student did not attempt this question or answer was not recorded.
                                            </div>
                                        @else
                                            <div
                                                class="rounded form-control {{ $result['status'] === 'passed' ? 'alert-success border-success' : 'alert-danger border-danger' }}">
                                                <span class="text-secondary me-3 fw-bold">Your Answer: </span>

                                                @if(in_array($qType, ['writing', 'writing-answer']) && is_string($result['answer']) && str_contains($result['answer'], 'a:'))
                                                    {{-- It's a serialized array of images --}}
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
                                                            <span class="p-1 px-3 border border-dark rounded me-2 d-inline-block mb-2">
                                                                @if(is_string($key) && !is_numeric($key)) {{ $key }} = @endif {{ $val }}
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
                                                    <span
                                                        class="d-inline-block">{{ is_array($result['answer']) ? json_encode($result['answer']) : $result['answer'] }}</span>
                                                @endif
                                            </div>
                                        @endif
                                    </div>

                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="text-center mt-4">
                        <a href="{{ route('student.dashboard') }}" class="btn-cls py-2 px-4">Back to Dashboard</a>
                        <a href="{{ route('student.device-test') }}"
                            class="btn btn-outline-primary py-2 px-4 ms-2">Retest Device</a>
                    </div>

                </div>
            </div>
        </div>
    </section>
</div>