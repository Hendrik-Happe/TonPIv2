<section class="mt-10 space-y-6">
    <div class="relative mb-5">
        <h3 class="text-xl font-semibold">{{ __('Delete account') }}</h3>
        <p class="opacity-70">{{ __('Delete your account and all of its resources') }}</p>
    </div>

    <div x-data="{ open: false }" class="space-y-4">
        <button class="btn btn-error" type="button" @click="open = true">
            {{ __('Delete account') }}
        </button>

        <div x-show="open" x-cloak class="card bg-base-100 border border-base-300 shadow-sm">
            <form method="POST" wire:submit="deleteUser" class="card-body space-y-6">
                <div>
                    <h4 class="text-lg font-semibold">{{ __('Are you sure you want to delete your account?') }}</h4>

                    <p class="opacity-70">
                        {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
                    </p>
                </div>

                <fieldset class="fieldset">
                    <legend class="fieldset-legend">{{ __('Password') }}</legend>
                    <input wire:model="password" type="password" class="input input-bordered w-full" />
                </fieldset>

                <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                    <button class="btn" type="button" @click="open = false">{{ __('Cancel') }}</button>

                    <button class="btn btn-error" type="submit">{{ __('Delete account') }}</button>
                </div>
            </form>
        </div>
    </div>
</section>
