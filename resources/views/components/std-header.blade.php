<section class="page-title">


    <div class="dropdown text-end p-2">
        <a href="{{ route('student.logout') }}" class="dropdown-text btn btn-outline-light">Logout</a>

    </div>

    <div class="container py-1">
        <div class="row">
            <div class="d-lg-flex justify-content-between align-items-center">
                <div class="text-center">
                    <h3 class="fw-bold mb-3 text-white">
                        {{ auth()->user()?->name }}</h3>

                    <div class="d-flex">
                        <h5 class="text-white"><b>Class :</b>
                            {{ auth()->user()?->Grade?->name }}
                        </h5>
                        <h5 class="text-white">
                            <b class="ms-4">Division :</b> {{ auth()->user()?->Section?->name }}
                        </h5>
                        <h5 class="text-white">
                            <b class="ms-4">Level :</b> {{ auth()->user()?->assignedLevel?->name ?? 'N/A' }}
                        </h5>
                    </div>
                </div>
                <div>
                    <div class="d-flex justify-content-center align-items-center">
                        <div class="d-flex justify-content-center align-items-center rounded-circle bg-light"
                            style="width: 150px; height: 150px;">
                            <img src="{{ image('uploads/logo', config('options.logo'), 'dummy-logo.png') }}" alt="Logo"
                                class="img-fluid">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</section>