<div>
    <x-std-header :title="'Personal Information <span>المعلومات الشخصية</span>'" />
    <section class="student-info mt-5">
        <div class="container">
            <div class="row">
                <div class="wrapper p-3 p-lg-5">
                    <div class="d-flex gap-2 align-items-center justify-content-between">
                        <h2 class="mb-4 fw-bold">Student Information</h2>
                        <a href="{{ route('student.device-test') }}" class="btn btn-outline-primary py-2 px-3 fw-bold">
                            <i class="fa-solid fa-desktop me-2"></i>
                            Test Device
                        </a>
                    </div>
                    <p><b>School : </b>{{ auth()->user()->School->name }}</p>
                    <p><b>Class : </b>{{ auth()->user()->Grade?->name ?? 'N/A' }}</p>
                    <p><b>Division : </b>{{ auth()->user()->Section?->name ?? 'N/A' }}</p>
                    <p><b>Registration number : </b>{{ auth()->user()->registration }}</p>
                    <p><b>Level : </b>{{ auth()->user()->assignedLevel?->name ?? 'N/A' }}</p>
                    <p><b>Nationality : </b>{{ auth()->user()->nationality }}</p>
                    <p><b>Category : </b>{{ auth()->user()->category }}</p>
                </div>
            </div>
            <div class="row">
                <div class="buttons d-inline-flex justify-content-between align-items-center my-4">
                    <a href="" class="pre-btn py-2 px-3">Previous</a>
                    <a href="{{ route('student.instructions') }}" class="btn-cls py-2 px-3"> Next</a>
                </div>
            </div>
        </div>
    </section>
</div>