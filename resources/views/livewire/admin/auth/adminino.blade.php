<div class="ast-auth__card">

    <span class="ast-auth__badge">
        <img src="{{ image('uploads/logo', config('options.logo'), 'dummy-logo.png') }}"
            alt="{{ config('options.web_name', 'Arabic Skill Benchmark Test') }}" width="500" height="216">
    </span>

    <p class="ast-auth__eyebrow">Admin portal <span class="ar" lang="ar">بوابة الإدارة</span></p>
    <h1 class="ast-auth__heading">Sign in to AST</h1>
    <p class="ast-auth__sub">Manage schools, exams and results.</p>

    <div class="ast-auth__rule" aria-hidden="true">
        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
            <path d="M8 0l1.8 3.1L13.2 2l-.8 3.5 3.6.5-2.8 2.2 2.8 2.2-3.6.5.8 3.5-3.4-1.1L8 16l-1.8-3.1L2.8 14l.8-3.5L0 10l2.8-2.2L0 5.6l3.6-.5L2.8 2l3.4 1.1z" />
        </svg>
    </div>

    @if ($failure)
        <p class="ast-auth__alert" role="alert">{{ $failure }}</p>
    @endif

    <form wire:submit.prevent="login" x-data="{ reveal: false }">

        <div class="ast-auth__field">
            <label class="ast-auth__label" for="email">Email</label>
            <div class="ast-auth__control">
                <span class="ast-auth__lead">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="2" y="4" width="20" height="16" rx="2" />
                        <path d="m2 7 10 6 10-6" />
                    </svg>
                </span>
                <input wire:model.defer="email" type="email" id="email" autocomplete="username"
                    class="ast-auth__input" placeholder="name@school.ae" autofocus>
            </div>
            @error('email')
                <span class="ast-auth__error" role="alert">{{ $message }}</span>
            @enderror
        </div>

        <div class="ast-auth__field">
            <label class="ast-auth__label" for="password">Password</label>
            <div class="ast-auth__control">
                <span class="ast-auth__lead">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="4" y="10" width="16" height="11" rx="2" />
                        <path d="M8 10V7a4 4 0 0 1 8 0v3" />
                    </svg>
                </span>
                {{-- Revealed in the browser: the password never goes to the server to be looked at. --}}
                <input wire:model.defer="password" type="password" x-bind:type="reveal ? 'text' : 'password'"
                    id="password" autocomplete="current-password"
                    class="ast-auth__input ast-auth__input--password" placeholder="Enter your password">
                {{-- Both icons are drawn and CSS picks one off the button's class.
                     x-show sets a style on the element, which an SVG root does not
                     honour here; a class on the button does. --}}
                <button type="button" class="ast-auth__peek" x-on:click="reveal = !reveal"
                    x-bind:class="reveal && 'is-revealed'" x-bind:aria-pressed="reveal"
                    x-bind:aria-label="reveal ? 'Hide password' : 'Show password'">
                    <svg class="ast-auth__peek-on" width="19" height="19" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"
                        aria-hidden="true">
                        <path d="M1.5 12S5 5.5 12 5.5 22.5 12 22.5 12 19 18.5 12 18.5 1.5 12 1.5 12Z" />
                        <circle cx="12" cy="12" r="3.2" />
                    </svg>
                    <svg class="ast-auth__peek-off" width="19" height="19" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"
                        aria-hidden="true">
                        <path d="M1.5 12S5 5.5 12 5.5 22.5 12 22.5 12 19 18.5 12 18.5 1.5 12 1.5 12Z" />
                        <circle cx="12" cy="12" r="3.2" />
                        <path d="m3 3 18 18" />
                    </svg>
                </button>
            </div>
            @error('password')
                <span class="ast-auth__error" role="alert">{{ $message }}</span>
            @enderror
        </div>

        <button type="submit" class="ast-auth__submit" wire:loading.attr="disabled" wire:target="login">
            <span wire:loading wire:target="login" aria-hidden="true">
                <span class="ast-auth__spinner"></span>
            </span>
            Sign in
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M5 12h14m-6-7 7 7-7 7" />
            </svg>
        </button>

    </form>

    <p class="ast-auth__foot">
        Forgot your password? Ask another admin to set a new one for you from the Admins page.
    </p>

</div>
