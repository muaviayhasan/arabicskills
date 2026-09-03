<div>
    <x-std-header :title="'Exam Instructions'" />
    <section class="student-info mt-5">
        <div class="container">
            <div class="row">
                <div class="wrapper instruction p-3 p-lg-5">
                    {!! $instructions !!}
                </div>
            </div>
            <div class="row">
                @php
                    $tp = str_replace("_","-",$type)
                @endphp
                <div class="buttons d-inline-flex justify-content-between align-items-center my-4">
                    <a href="{{ route('student.exams') }}" class="pre-btn py-2 px-3">Previous</a>
                    <a href="{{ route("student.{$tp}-exam") }}" class="btn-cls py-2 px-3"> Next</a>
                </div>
            </div>
        </div>
    </section>
</div>
