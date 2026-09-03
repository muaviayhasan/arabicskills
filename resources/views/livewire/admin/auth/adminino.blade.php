<div>
    <div class="row justify-content-center my-auto">
        <div class="col-md-8 col-lg-6 col-xl-5">

            <div class="mb-4 pb-2">
                <a href="{{ route('admin.dashboard') }}" class="d-block auth-logo">
                    <img src="{{ image('uploads/logo', config('options.logo'), 'dummy-logo.png') }}" alt=""
                        height="30" class="auth-logo-dark me-start">
                    <img src="{{ image('uploads/logo', config('options.logo'), 'dummy-logo.png') }}" alt=""
                        height="30" class="auth-logo-light me-start">
                </a>
            </div>

            <div class="card">
                <div class="card-body p-4">
                    <div class="text-center mt-2">
                        <h5>Admin Login Portal</h5>
                        <p class="text-muted">Login to continue to {{ config('options.web_name', 'Sarhad Premium') }}
                        </p>
                    </div>
                    <div class="p-2 mt-4">
                        <form wire:submit.prevent="login">

                            <div class="mb-3">
                                <label class="form-label" for="username">Username</label>
                                <div class="position-relative input-custom-icon">
                                    <input wire:model.defer="email" type="text" class="form-control" id="username"
                                        placeholder="Enter email">
                                    <span class="bx bx-user"></span>
                                </div>
                                @error('email')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="password-input">Password</label>
                                <div class="position-relative auth-pass-inputgroup input-custom-icon">
                                    <span class="bx bx-lock-alt"></span>
                                    <input wire:model.defer="password" type="{{ $showPass ? 'password' : 'text' }}"
                                        class="form-control" id="password-input" placeholder="Enter password">
                                    <button wire:click.prevent="$toggle('showPass')" type="button"
                                        class="btn btn-link position-absolute h-100 end-0 top-0" id="password-addon">
                                        <i class="mdi mdi-eye-outline font-size-18 text-muted"></i>
                                    </button>
                                </div>
                                @error('password')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-check py-1">
                                <input type="checkbox" class="form-check-input" id="auth-remember-check">
                                <label class="form-check-label" for="auth-remember-check">Remember
                                    me</label>
                            </div>

                            <div class="mt-3">
                                <button class="btn btn-primary w-100 waves-effect waves-light" type="submit">
                                    <div wire:loading wire:target="login">
                                        <i class="bx bx-loader bx-spin font-size-16 align-middle me-2"></i>
                                    </div> Log
                                    In
                                </button>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div><!-- end col -->
    </div><!-- end row -->
</div>
