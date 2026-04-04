<x-layouts::auth :title="__('Register')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Create an account')" :description="__('Enter your details below to create your account')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-6">
            @csrf
            <!-- Name -->
            <fieldset class="fieldset">
                <legend class="fieldset-legend">{{ __('Name') }}</legend>
                <input name="name" value="{{ old('name') }}" type="text" required autofocus autocomplete="name" placeholder="{{ __('Full name') }}" class="input input-bordered w-full" />
            </fieldset>

            <!-- Email Address -->
            <fieldset class="fieldset">
                <legend class="fieldset-legend">{{ __('Email address') }}</legend>
                <input name="email" value="{{ old('email') }}" type="email" required autocomplete="email" placeholder="email@example.com" class="input input-bordered w-full" />
            </fieldset>

            <!-- Password -->
            <fieldset class="fieldset">
                <legend class="fieldset-legend">{{ __('Password') }}</legend>
                <input name="password" type="password" required autocomplete="new-password" placeholder="{{ __('Password') }}" class="input input-bordered w-full" />
            </fieldset>

            <!-- Confirm Password -->
            <fieldset class="fieldset">
                <legend class="fieldset-legend">{{ __('Confirm password') }}</legend>
                <input name="password_confirmation" type="password" required autocomplete="new-password" placeholder="{{ __('Confirm password') }}" class="input input-bordered w-full" />
            </fieldset>

            <div class="flex items-center justify-end">
                <button type="submit" class="btn btn-primary w-full" data-test="register-user-button">
                    {{ __('Create account') }}
                </button>
            </div>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
            <span>{{ __('Already have an account?') }}</span>
            <a href="{{ route('login') }}" wire:navigate class="link link-primary">{{ __('Log in') }}</a>
        </div>
    </div>
</x-layouts::auth>
