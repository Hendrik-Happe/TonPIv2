<x-layouts::auth :title="__('Confirm password')">
    <div class="flex flex-col gap-6">
        <x-auth-header
            :title="__('Confirm password')"
            :description="__('This is a secure area of the application. Please confirm your password before continuing.')"
        />

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.confirm.store') }}" class="flex flex-col gap-6">
            @csrf

            <fieldset class="fieldset">
                <legend class="fieldset-legend">{{ __('Password') }}</legend>
                <input name="password" type="password" required autocomplete="current-password" placeholder="{{ __('Password') }}" class="input input-bordered w-full" />
            </fieldset>

            <button class="btn btn-primary w-full" type="submit" data-test="confirm-password-button">
                {{ __('Confirm') }}
            </button>
        </form>
    </div>
</x-layouts::auth>
