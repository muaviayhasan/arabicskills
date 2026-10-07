<section class="login-page">
    <div class="container-fluid p-0">
        <div class="row row-cols-1 row-cols-lg-2 g-0">
            <div class="col p-0">
                <div class="bg-img w-100">
                    <img src="{{ asset('includes') }}/images/login-classroom.jpg"
                        alt="Students sitting the Arabic Skill Benchmark Test in a classroom">
                </div>
            </div>
            <div class="col p-4 p-lg-3 login-form-col">
                <div class="form-box mt-4 mt-lg-1 pt-0 pt-lg-2 position-relative">
                    <div class="logo">
                        <img src="{{ image('uploads/logo', config('options.logo'), 'dummy-logo.png') }}" alt="">
                    </div>
                    <div class="login-heading mt-4 mt-lg-0 pt-0 pt-lg-4">
                        <h1>Login to your account</h1>
                    </div>
                    <div class="form-sec mt-4 mt-lg-3">
                        <form wire:submit.prevent="login">
                            <div class="mb-4">
                                <label for="username#" class="form-label">Student Username</label>
                                <input type="text" wire:model.defer="username" class="form-control p-3"
                                    id="username#" placeholder="Enter your student username">
                                @error('username')
                                    <span class="text-danger">The username field is required</span>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="Password" class="form-label">Password</label>
                                <div class="position-relative auth-pass-inputgroup input-custom-icon">
                                    <span class="bx bx-lock-alt"></span>
                                    <input id="passwordInput" wire:model.defer="password" type="password"
                                        class="form-control p-3" id="password-input" placeholder="Enter password">
                                    <button id="toggleButton" type="button"
                                        class=" border-0 bg-transparent p-1 position-absolute end-0 font-size-18 text-muted top-50 translate-middle"
                                        id="password-addon">

                                        <i class="fa-regular fa-eye"></i>

                                    </button>
                                </div>
                                @error('password')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <button type="submit"
                                class="py-2 px-5 w-100 btn-cls text-center d-flex align-items-center justify-content-center gap-3">
                                <div wire:loading wire:target="login" class="spinner-border small"
                                    role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                Login
                            </button>
                        </form>
                    </div>
                    <div class="copyright">
                        © Copyrights, {{ date('Y') }} {{ config('options.web_name') }}
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>
