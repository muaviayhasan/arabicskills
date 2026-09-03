<div>
    <x-exam-header :title="'Sentences Structures Comprehension <span>فهم بنية الجمل</span>'" :remainingSeconds="$remainingSeconds" />

    <section class="quiz">
        <div class="container">
            <div class="row">
                <form id="sentences_structures_exam">
                    @csrf
                    <input type="text" name="exam_id" value="{{ $student_exam->id }}" hidden>
                    <div class="quiz-items">
                        @foreach ($activities as $i => $activity)
                            <h4 class="activity-paragraph question mt-4 d-flex" tabindex="0">
                                <span class="text-danger fs-5 me-3 fw-bold text-nowrap">Q-{{ $i + 1 }}</span>
                                <div class="">
                                    {!! $activity->activity !!}
                                </div>
                            </h4>
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
