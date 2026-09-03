<div>
    <x-std-header :title="'Instructions'" />
    <section class="student-info mt-5">
        <div class="container">
            <div class="row">
                <div class="wrapper instruction p-3 p-lg-5">
                    {!! $instructions !!}
                </div>
            </div>
            <div class="row">
                <div class="buttons d-inline-flex justify-content-between align-items-center my-4">
                    <a href="{{ route('student.dashboard') }}" class="pre-btn py-2 px-3">Previous</a>
                    <a href="{{ route('student.exams') }}" class="btn-cls py-2 px-3"> Next</a>
                </div>
            </div>
        </div>
    </section>
</div>
