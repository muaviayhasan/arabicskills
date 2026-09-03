<div>
    <x-exam-header :title="'Writing Expression<span> التعبير الكتابي </span>'" :remainingSeconds="$remainingSeconds"/>
    <section class="quiz">
        <div class="container">
            <div class="row">
                <form id="writing_exam">
                    @csrf
                    <input type="text" name="exam_id" value="{{ $student_exam->id }}" hidden>
                    <div class="quiz-items">
                        @forelse($activities as $i => $activity)
                        <div class="ms-5 mt-3">
                            <h4 class="activity-paragraph question my-4 d-flex" tabindex="0">
                                <span class="text-danger fs-5 me-3 fw-bold">Q-{{ $i + 1 }}</span>
                                <div>
                                    {!! $activity->activity !!}
                                </div>
                            </h4>
                            @if ($activity->image)

                            <div class="text-center">
                                <img src="{{read_image($activity->image)}}" alt="{{ $activity->title}}" style="max-width: 100%;" height="300px">
                            </div>

                            @endif
                            @php
                            $j = 0;
                            @endphp
                            @foreach ($activity->Question as $k => $question)
                            @php
                            $j = $j + 1;
                            @endphp
                            @if ($question->type == 'typing' || $question->type == 'writing')
                            @include('partials.answer_types.writing-answer', [
                            'question' => $question,
                            'index' => $j,
                            ])
                            @else
                            @include("partials.answer_types.{$question->type}", [
                            'question' => $question,
                            'index' => $j,
                            ])
                            @endif
                            @endforeach
                        </div>
                        @empty
                        <div class="text-center">
                            <h3 class="text-danger fs-5 me-3 fw-bold">No Question found.</h3>
                        </div>
                        @endforelse

                        <button type="submit" class="btn-cls py-2 px-3 my-4 d-inline-block">
                            Submit Exam</button>
                    </div>
                </form>

            </div>

            <!-- Swiper JS -->
        </div>
    </section>
</div>