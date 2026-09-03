<div>
    <x-exam-header :title="'Listening Comprehension <span>فهم المسموع</span>'" :remainingSeconds="$remainingSeconds" />

    <section class="quiz">
        <div class="container">
            <div class="row">
                <form id="listening_exam">
                    @csrf
                    <input type="text" name="exam_id" value="{{ $student_exam->id }}" hidden>
                    <h4 class="question my-4" tabindex="0">Listen the audio carefully and
                        answer the questions given below.</h4>
                    <div class="quiz-items">
                        @foreach ($activities as $i => $activity)
                            <div class="activity-paragraph d-flex gap-3 mt-3">
                                <span class="text-danger fs-5 me-3 fw-bold text-nowrap">Q{{ $i + 1 }} <i
                                        class="fa-solid fa-arrow-right"></i></span>
                                <audio controls controlsList="nodownload noplaybackrate">
                                    <source src="{{ read_image($activity->activity) }}" type="audio/mpeg">
                                </audio>

                            </div>
                            @if ($activity->image)
                                <div class="text-center">
                                    <img src="{{ read_image($activity->image) }}" alt="{{ $activity->title }}"
                                        style="max-width: 100%;" height="300px">
                                </div>
                            @endif
                            <div class="ms-5">
                                @forelse ($activity->Question as $i => $question)
                                    @include("partials.answer_types.{$question->type}", [
                                        'question' => $question,
                                        'index' => $i,
                                    ])
                                @empty
                                    <div class="text-center">
                                        <h3 class="text-danger fs-5 me-3 fw-bold">No Question found.</h3>
                                    </div>
                                @endforelse
                            </div>
                        @endforeach

                        <button type="submit" class="btn-cls py-2 px-3 my-4 d-inline-block">
                            Submit Exam</button>
                    </div>
                </form>

            </div>

            <!-- Swiper JS -->
        </div>
    </section>
</div>
