<x-layouts::auth :title="__('Log in')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Log in to your account')" :description="__('Enter your email and password below to log in')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Email Address -->
            <fieldset class="fieldset">
                <legend class="fieldset-legend">{{ __('Email address') }}</legend>
                <input name="email" value="{{ old('email') }}" type="email" required autofocus autocomplete="email" placeholder="email@example.com" class="input input-bordered w-full" />
            </fieldset>

            <!-- Password -->
            <div class="relative">
                <fieldset class="fieldset">
                    <legend class="fieldset-legend">{{ __('Password') }}</legend>
                    <input name="password" type="password" required autocomplete="current-password" placeholder="{{ __('Password') }}" class="input input-bordered w-full" />
                </fieldset>

                @if (Route::has('password.request'))
                    <a class="absolute top-0 end-0 link link-primary text-sm" href="{{ route('password.request') }}" wire:navigate>
                        {{ __('Forgot your password?') }}
                    </a>
                @endif
            </div>

            <!-- Remember Me -->
            <label class="label cursor-pointer justify-start gap-3">
                <input name="remember" type="checkbox" class="checkbox checkbox-primary" @checked(old('remember')) />
                <span class="label-text">{{ __('Remember me') }}</span>
            </label>

            <div class="flex items-center justify-end">
                <button class="btn btn-primary w-full" type="submit" data-test="login-button">
                    {{ __('Log in') }}
                </button>
            </div>
        </form>

        @if (Route::has('register'))
            <div class="space-x-1 text-sm text-center rtl:space-x-reverse text-zinc-600 dark:text-zinc-400">
                <span>{{ __('Don\'t have an account?') }}</span>
                <a href="{{ route('register') }}" wire:navigate class="link link-primary">{{ __('Sign up') }}</a>
            </div>
        @endif
    </div>
</x-layouts::auth>
