<x-layouts::auth :title="__('Forgot password')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Forgot password')" :description="__('Enter your email to receive a password reset link')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Email Address -->
            <fieldset class="fieldset">
                <legend class="fieldset-legend">{{ __('Email address') }}</legend>
                <input name="email" type="email" required autofocus placeholder="email@example.com" class="input input-bordered w-full" />
            </fieldset>

            <button class="btn btn-primary w-full" type="submit" data-test="email-password-reset-link-button">
                {{ __('Email password reset link') }}
            </button>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-400">
            <span>{{ __('Or, return to') }}</span>
            <a href="{{ route('login') }}" wire:navigate class="link link-primary">{{ __('log in') }}</a>
        </div>
    </div>
</x-layouts::auth>
