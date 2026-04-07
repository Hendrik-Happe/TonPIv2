<section class="w-full">
    @include('partials.settings-heading')

    <h2 class="sr-only">{{ __('Security settings') }}</h2>

    <x-settings.layout :heading="__('Update password')" :subheading="__('Ensure your account is using a long, random password to stay secure')">
        <form method="POST" wire:submit="updatePassword" class="mt-6 space-y-6">
            <fieldset class="fieldset">
                <legend class="fieldset-legend">{{ __('Current password') }}</legend>
                <input wire:model="current_password" type="password" required autocomplete="current-password" class="input input-bordered w-full" />
                @error('current_password')
                    <label class="label">
                        <span class="label-text-alt text-error">{{ $message }}</span>
                    </label>
                @enderror
            </fieldset>
            <fieldset class="fieldset">
                <legend class="fieldset-legend">{{ __('New password') }}</legend>
                <input wire:model="password" type="password" required autocomplete="new-password" class="input input-bordered w-full" />
                @error('password')
                    <label class="label">
                        <span class="label-text-alt text-error">{{ $message }}</span>
                    </label>
                @enderror
            </fieldset>
            <fieldset class="fieldset">
                <legend class="fieldset-legend">{{ __('Confirm password') }}</legend>
                <input wire:model="password_confirmation" type="password" required autocomplete="new-password" class="input input-bordered w-full" />
                @error('password_confirmation')
                    <label class="label">
                        <span class="label-text-alt text-error">{{ $message }}</span>
                    </label>
                @enderror
            </fieldset>

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <button class="btn btn-primary w-full" type="submit" data-test="update-password-button">{{ __('Save') }}</button>
                </div>

                <x-action-message class="me-3" on="password-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>
    </x-settings.layout>
</section>
