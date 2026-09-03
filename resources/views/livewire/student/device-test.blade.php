<div>
    <x-std-header :title="'Device Test <span>اختبار الجهاز</span>'" />

    <section class="quiz mt-4">
        <div class="container">
            <div class="row">
                <div class="col-12 mb-4">
                    <div class="alert alert-info">
                        <h5><i class="fa-solid fa-circle-info"></i> Welcome to the Device Test</h5>
                        <p class="mb-0">Please interact with each question below and then submit the test to verify that your device supports all question types.</p>
                    </div>
                </div>

                <form id="device_test_form" method="POST" action="{{ route('student.device-test.submit') }}">
                    @csrf
                    <input type="hidden" name="device_info" id="device_info_input" value="{}">
                    
                    <div class="quiz-items">
                        @foreach ($questions as $i => $question)
                            <h4 class="activity-paragraph question mt-4 d-flex" tabindex="0">
                                <span class="text-danger fs-5 me-3 fw-bold text-nowrap">Test {{ $i + 1 }}</span>
                            </h4>

                            <div class="ms-5">
                                @if ($question->type === "match")
                                    @include("partials.answer_types.fake_match", [
                                        'question' => $question,
                                        'index' => $i,
                                    ])
                                @else
                                                    @include("partials.answer_types.$question->type", [
                                                        'question' => $question,
                                                        'index' => $i,
                                                    ])
                                                @endif
                            </div>
                        @endforeach

                        <button type="submit" class="btn-cls py-2 px-3 my-4 d-inline-block">
                            Submit Device Test
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</div>


