<div>
    <div class="row justify-content-center align-items-center g-2">
        <div class="col-4">
            <div class="alert alert-success text-center p-3" role="alert">
                Correct Answer
            </div>
        </div>
        <div class="col-4">
            <div class="alert alert-danger text-center p-3" role="alert">
                Wrong Answer
            </div>
        </div>
        <div class="col-4">
            <div class="alert alert-primary text-center p-3" role="alert">
                Verified
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">

            @foreach ($activities as $activity)
            <div class="mb-5 border border-primary p-2 rounded">
                <h4>Main Assessment</h4>
                <div class="d-flex mb-3">
                    @if ($activity->type == 'listening')
                    <audio controlsList="nodownload noplaybackrate" class="mt-5" controls>
                        <source src="{{ read_image($activity->activity) }}" type="audio/mpeg">
                        Your browser does not support the audio element.
                    </audio>
                    @else
                    <p class="mt-3">{!! $activity->activity !!}</p>
                    @endif

                    @if ($activity->image)
                    <img src="{{ read_image($activity->image) }}" class="mx-auto rounded ml-auto" width="400px"
                        height="150px">
                    @endif
                </div>
                <h5>Question and Answers</h5>
                @if ($type == 'reading' || $type == 'listening'|| $type== "sentences_structures")
                @foreach ($activity->Question as $question)
                @if ($question->type == 'blanks')
                <strong>Q-> {!! $question->question !!}</strong>

                <div class="row g-2">
                    <div class="col-md-6">
                        <span class="rounded form-control alert-primary">
                            <span class="text-secondary me-5">Student's Answer : </span>
                            {{ optional($question->TakeExam)->answer }}</span>
                    </div>
                    <div class="col-md-6">
                        <input type="number" value="{{ $inputs[$question->id] ?? 0 }}" readonly
                            class="rounded form-control" placeholder="marks for question">
                    </div>

                </div>
                @elseif ($question->type == 'MCQs')
                <strong>Q-> {!! $question->question !!}</strong>
                <div class="row g-2">

                    <div class="col-6 col-md-3">(a) {{ $question->options['a'] }}</div>
                    <div class="col-6 col-md-3">(b) {{ $question->options['b'] }}</div>
                    <div class="col-6 col-md-3">(c) {{ $question->options['c'] }}</div>
                    <div class="col-6 col-md-3">(d) {{ $question->options['d'] }}</div>
                    <div class="col-md-6">
                        <span
                            class="rounded form-control {{ optional($question->TakeExam)->answer == $question->correct_answer ? 'alert-success' : 'alert-danger' }}">
                            <span class="text-secondary me-5">Student's Answer : </span>
                            {{ optional($question->TakeExam)->answer }}</span>
                    </div>
                    <div class="col-md-6">
                        <input type="number" value="{{ $inputs[$question->id] ?? 0 }}" readonly
                            class="rounded form-control" placeholder="marks for question">
                    </div>
                </div>
                @elseif ($question->type == 'true-false')
                <strong>Q-> {!! $question->question !!}</strong>
                <div class="row g-2">
                    <div class="col-md-6">
                        <span
                            class="rounded form-control {{ optional($question->TakeExam)->answer == $question->correct_answer ? 'alert-success' : 'alert-danger' }}">
                            <span class="text-secondary me-5">Student's Answer : </span>
                            {{ optional($question->TakeExam)->answer }}</span>
                    </div>
                    <div class="col-md-6">
                        <input type="number" value="{{ $inputs[$question->id] ?? 0 }}" readonly
                            class="rounded form-control" placeholder="marks for question">
                    </div>

                </div>
                @elseif ($question->type == 'rearrange')
                <strong>Q->Rearrange words</strong>
                @php
                $array = explode(',', $question->question);

                @endphp
                <div class="p-3">
                    @foreach ($array as $arr)
                    <span class="p-1 border border-primary">{{ $arr }}</span>
                    @endforeach
                </div>
                <div class="row g-2">
                    <div class="col-md-6">
                        <span class="rounded form-control alert-primary">
                            <span class="text-secondary">Answer : </span>
                            @php
                            $answerRerrange = !is_null($question->TakeExam->answer)
                            ? json_decode($question->TakeExam->answer)
                            : [];

                            $answerRerrange = !empty($answerRerrange) ? $answerRerrange : [];

                            @endphp

                            @foreach ($answerRerrange as $word)
                            <span class="p-1 border border-dark">{{ $word }}</span>
                            @endforeach
                        </span>
                    </div>
                    <div class="col-md-6">
                        <input type="number" wire:model="inputs.{{ $question->id }}" class="rounded form-control"
                            placeholder="Enter obtained marks for question">
                    </div>
                </div>
                @elseif ($question->type == 'typing')
                <strong>Q-> {!! $question->question !!} </strong>
                <div class="row g-2">
                    <div class="col-12 mb-3">
                        <span class="rounded form-control alert-primary">
                            <span class="text-secondary">
                                Answer :
                            </span>
                            {{ optional($question->TakeExam)->answer }}
                        </span>
                    </div>
                    <div class="col-12">


                    </div>
                    <div class="col-12 d-flex">
                        <h6>Obtained Marks</h6>
                        <input type="number" value="{{ $inputs[$question->id] ?? 0 }}" readonly
                            class="rounded form-control" placeholder="marks for question">
                    </div>
                </div>
                @elseif ($question->type == 'match')
                @php
                $answers = json_decode($question->TakeExam->answer ?? json_encode([]));
                @endphp
                <strong>Q-> {!! $question->question !!}</strong> <br>
                <div class="row justify-content-center align-items-center g-2">
                    @foreach ($answers as $i => $ans)

                    <div class="col-2 col-md-2 text-center alert">
                        <div class="border">
                            @if(is_numeric($i))
                            <img src="{{ read_image($i) }}" alt="" height="120" width="100%">
                            @else
                            <h6>{{ $i }}</h6>
                            @endif
                            <hr class="bg-primary">
                            @if(is_numeric($ans))
                            <img src="{{ read_image($ans) }}" alt="" height="120" width="100%">
                            @else
                            <h6>{{ $ans }}</h6>
                            @endif
                        </div>
                    </div>

                    @endforeach
                    <div class="col-12 ps-5 pe-5">
                        <h6>Obtained Marks</h6>
                        <input type="number" value="{{ $inputs[$question->id] ?? 0 }}" readonly
                            class="rounded form-control" placeholder="marks for question">
                    </div>
                </div>
                @endif

                <hr class="mb-3">
                @endforeach
                @elseif ($type == 'writing')
                @foreach ($activity->Question as $question)
                @if ($question->type == 'MCQs')
                <strong>Q-> {!! $question->question !!}</strong>
                <div class="row g-2">

                    <div class="col-6 col-md-3">(a) {{ $question->options['a'] }}</div>
                    <div class="col-6 col-md-3">(b) {{ $question->options['b'] }}</div>
                    <div class="col-6 col-md-3">(c) {{ $question->options['c'] }}</div>
                    <div class="col-6 col-md-3">(d) {{ $question->options['d'] }}</div>
                    <div class="col-md-6">
                        <span
                            class="rounded form-control {{ optional($question->TakeExam)->answer == $question->correct_answer ? 'alert-success' : 'alert-danger' }}">
                            <span class="text-secondary me-5">Student's Answer : </span>
                            {{ optional($question->TakeExam)->answer }}</span>
                    </div>
                    <div class="col-md-6">
                        <input type="number" value="{{ $inputs[$question->id] ?? 0 }}" readonly
                            class="rounded form-control" placeholder="marks for question">
                    </div>
                </div>
                @elseif ($question->type == 'true-false')
                <strong>Q-> {!! $question->question !!}</strong>
                <div class="row g-2">
                    <div class="col-md-6">
                        <span
                            class="rounded form-control {{ optional($question->TakeExam)->answer == strtoupper($question->correct_answer) ? 'alert-success' : 'alert-danger' }}">
                            <span class="text-secondary me-5">Student's Answer : </span>
                            {{ optional($question->TakeExam)->answer }}</span>
                    </div>
                    <div class="col-md-6">
                        <input type="number" value="{{ $inputs[$question->id] ?? 0 }}" readonly
                            class="rounded form-control" placeholder="marks for question">
                    </div>

                </div>
                @elseif (isset($question->TakeExam) && $question->TakeExam->type == 'writing')
                <strong>Q-> {!! $question->question !!} </strong>
                <div class="row g-2">
                    @php

                    $anses = is_string($question->TakeExam->answer)
                    ? unserialize($question->TakeExam->answer)
                    : [];

                    @endphp
                    <div class="col-12" style="max-height: 400px; overflow-y: auto;">
                        @foreach ($anses as $an)
                        <img src="{{ read_image($an) }}" alt="" class="w-100">
                        @endforeach
                    </div>

                    <div class="col-12">
                        <h6>Obtained Marks</h6>
                        <input type="number" value="{{ $inputs[$question->id] ?? 0 }}" readonly
                            class="rounded form-control" placeholder="marks for question">
                    </div>
                </div>
                @elseif (isset($question->TakeExam) && $question->TakeExam->type == 'typing')
                <strong>Q-> {!! $question->question !!} </strong>
                <div class="row g-2">
                    <div class="col-12">
                        <span class="rounded form-control alert-primary">
                            <span class="text-secondary">
                                Answer :
                            </span>
                            {{ optional($question->TakeExam)->answer }}
                        </span>
                    </div>
                    <div class="col-12">
                        <h6>Obtained Marks</h6>
                        <input type="number" value="{{ $inputs[$question->id] ?? 0 }}" readonly
                            class="rounded form-control" placeholder="marks for question">
                    </div>
                </div>
                @endif

                <hr class="mb-3">
                @endforeach
                @elseif ($type == 'speaking')
                @foreach ($activity->Question as $question)
                @if ($question->type == 'MCQs')
                <strong>Q-> {!! $question->question !!}</strong>
                <div class="row g-2">

                    <div class="col-6 col-md-3">(a) {{ $question->options['a'] }}</div>
                    <div class="col-6 col-md-3">(b) {{ $question->options['b'] }}</div>
                    <div class="col-6 col-md-3">(c) {{ $question->options['c'] }}</div>
                    <div class="col-6 col-md-3">(d) {{ $question->options['d'] }}</div>
                    <div class="col-md-6">
                        <audio controlsList="nodownload noplaybackrate" controls>
                            <source src="{{ read_image(optional($question->TakeExam)->answer) }}" type="audio/mpeg">
                            Your browser does not support the audio element.
                        </audio>
                    </div>
                    <div class="col-md-6">
                        <input type="number" value="{{ $inputs[$question->id] ?? 0 }}" readonly
                            class="rounded form-control" placeholder="marks for question">
                    </div>
                </div>
                @elseif ($question->type == 'true-false')
                <strong>Q-> {!! $question->question !!}</strong>
                <div class="row g-2">
                    <div class="col-md-6">
                        <audio controlsList="nodownload noplaybackrate" controls>
                            <source src="{{ read_image(optional($question->TakeExam)->answer) }}" type="audio/mpeg">
                            Your browser does not support the audio element.
                        </audio>
                    </div>
                    <div class="col-md-6">
                        <input type="number" value="{{ $inputs[$question->id] ?? 0 }}" readonly
                            class="rounded form-control" placeholder="marks for question">
                    </div>

                </div>
                @elseif ($question->type == 'typing' || $type == 'writing')
                <strong>Q-> {!! $question->question !!} </strong>
                <div class="row g-2">
                    <div class="col-md-6">
                        <audio controlsList="nodownload noplaybackrate" controls>
                            <source src="{{ read_image(optional($question->TakeExam)->answer) }}" type="audio/mpeg">
                            Your browser does not support the audio element.
                        </audio>
                    </div>
                    <div class="col-6">
                        <input type="number" value="{{ $inputs[$question->id] ?? 0 }}" readonly
                            class="rounded form-control" placeholder="marks for question">
                    </div>
                </div>
                @endif

                <hr class="mb-3">
                @endforeach
                @endif
            </div>
            @endforeach
            <h6>Total Obtained Marks Of Student</h6>
            <div class="row justify-content-center align-items-center g-2">
                <div class="col-12">
                    <input readonly wire:model.defer="marks" class="form-control">
                </div>

            </div>
        </div>
    </div>
</div>