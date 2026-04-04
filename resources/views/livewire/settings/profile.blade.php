<section class="w-full">
    @include('partials.settings-heading')

    <h2 class="sr-only">{{ __('Profile settings') }}</h2>

    <x-settings.layout :heading="__('Profile')" :subheading="__('Update your name and email address')">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <fieldset class="fieldset">
                <legend class="fieldset-legend">{{ __('Name') }}</legend>
                <input wire:model="name" type="text" required autofocus autocomplete="name" class="input input-bordered w-full" />
            </fieldset>

            <div>
                <fieldset class="fieldset">
                    <legend class="fieldset-legend">{{ __('Email') }}</legend>
                    <input wire:model="email" type="email" required autocomplete="email" class="input input-bordered w-full" />
                </fieldset>

                @if ($this->hasUnverifiedEmail)
                    <div>
                        <p class="mt-4 text-sm">
                            {{ __('Your email address is unverified.') }}

                            <button class="link link-primary text-sm cursor-pointer" wire:click.prevent="resendVerificationNotification" type="button">
                                {{ __('Click here to re-send the verification email.') }}
                            </button>
                        </p>

                        @if (session('status') === 'verification-link-sent')
                            <p class="mt-2 font-medium text-success">
                                {{ __('A new verification link has been sent to your email address.') }}
                            </p>
                        @endif
                    </div>
                @endif
            </div>

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <button class="btn btn-primary w-full" type="submit">{{ __('Save') }}</button>
                </div>

                <x-action-message class="me-3" on="profile-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>

        @if ($this->showDeleteUser)
            <livewire:settings.delete-user-form />
        @endif
    </x-settings.layout>
</section>
