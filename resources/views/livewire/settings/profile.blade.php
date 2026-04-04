<section class="w-full">
    @include('partials.settings-heading')

    <h2 class="sr-only">{{ __('Profile settings') }}</h2>

    <x-settings.layout :heading="__('Profile')" :subheading="__('Update your username')">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <fieldset class="fieldset">
                <legend class="fieldset-legend">{{ __('Username') }}</legend>
                <input wire:model="name" type="text" required autofocus autocomplete="username" class="input input-bordered w-full" />
            </fieldset>

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
