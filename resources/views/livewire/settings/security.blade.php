<section class="w-full">
    @include('partials.settings-heading')

    <h2 class="sr-only">{{ __('Security settings') }}</h2>

    <x-settings.layout :heading="__('Update password')" :subheading="__('Ensure your account is using a long, random password to stay secure')">
        <form method="POST" wire:submit="updatePassword" class="mt-6 space-y-6">
            <fieldset class="fieldset">
                <legend class="fieldset-legend">{{ __('Current password') }}</legend>
                <input wire:model="current_password" type="password" required autocomplete="current-password" class="input input-bordered w-full" />
            </fieldset>
            <fieldset class="fieldset">
                <legend class="fieldset-legend">{{ __('New password') }}</legend>
                <input wire:model="password" type="password" required autocomplete="new-password" class="input input-bordered w-full" />
            </fieldset>
            <fieldset class="fieldset">
                <legend class="fieldset-legend">{{ __('Confirm password') }}</legend>
                <input wire:model="password_confirmation" type="password" required autocomplete="new-password" class="input input-bordered w-full" />
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

        @if ($canManageTwoFactor)
            <section class="mt-12">
                <h3 class="text-xl font-semibold">{{ __('Two-factor authentication') }}</h3>
                <p class="opacity-70">{{ __('Manage your two-factor authentication settings') }}</p>

                <div class="flex flex-col w-full mx-auto space-y-6 text-sm" wire:cloak>
                    @if ($twoFactorEnabled)
                        <div class="space-y-4">
                            <p>
                                {{ __('You will be prompted for a secure, random pin during login, which you can retrieve from the TOTP-supported application on your phone.') }}
                            </p>

                            <div class="flex justify-start">
                                <button class="btn btn-error" wire:click="disable" type="button">
                                    {{ __('Disable 2FA') }}
                                </button>
                            </div>

                            <livewire:settings.two-factor.recovery-codes :$requiresConfirmation/>
                        </div>
                    @else
                        <div class="space-y-4">
                            <p class="opacity-70">
                                {{ __('When you enable two-factor authentication, you will be prompted for a secure pin during login. This pin can be retrieved from a TOTP-supported application on your phone.') }}
                            </p>

                            <button class="btn btn-primary" wire:click="enable" type="button">
                                {{ __('Enable 2FA') }}
                            </button>
                        </div>
                    @endif
                </div>
            </section>

            @if ($showModal)
                <div class="mt-6 card bg-base-100 border border-base-300 shadow-sm">
                    <div class="card-body space-y-6">
                        <div class="space-y-2 text-center">
                            <h4 class="text-lg font-semibold">{{ $this->modalConfig['title'] }}</h4>
                            <p class="opacity-70">{{ $this->modalConfig['description'] }}</p>
                        </div>

                        @if ($showVerificationStep)
                            <div class="space-y-6">
                                <div class="flex justify-center">
                                    <input
                                        name="code"
                                        wire:model="code"
                                        maxlength="6"
                                        inputmode="numeric"
                                        autocomplete="one-time-code"
                                        class="input input-bordered input-lg text-center tracking-[0.6em] max-w-xs"
                                    />
                                </div>

                                <div class="flex items-center space-x-3">
                                    <button class="btn flex-1" wire:click="resetVerification" type="button">
                                        {{ __('Back') }}
                                    </button>

                                    <button class="btn btn-primary flex-1" wire:click="confirmTwoFactor" type="button" @disabled(strlen($code) < 6)>
                                        {{ __('Confirm') }}
                                    </button>
                                </div>
                            </div>
                        @else
                            @error('setupData')
                                <div role="alert" class="alert alert-error">
                                    <span>{{ $message }}</span>
                                </div>
                            @enderror

                            <div class="flex justify-center">
                                <div class="relative w-64 overflow-hidden border rounded-lg border-base-300 aspect-square bg-base-100">
                                    @empty($qrCodeSvg)
                                        <div class="absolute inset-0 flex items-center justify-center animate-pulse">
                                            <span class="loading loading-spinner loading-md"></span>
                                        </div>
                                    @else
                                        <div class="flex items-center justify-center h-full p-4">
                                            <div class="bg-white p-3 rounded">{!! $qrCodeSvg !!}</div>
                                        </div>
                                    @endempty
                                </div>
                            </div>

                            <div>
                                <button class="btn btn-primary w-full" wire:click="showVerificationIfNecessary" type="button" @disabled($errors->has('setupData'))>
                                    {{ $this->modalConfig['buttonText'] }}
                                </button>
                            </div>

                            <div class="space-y-4">
                                <div class="divider">{{ __('or, enter the code manually') }}</div>

                                <div
                                    class="flex items-center space-x-2"
                                    x-data="{
                                        copied: false,
                                        async copy() {
                                            try {
                                                await navigator.clipboard.writeText('{{ $manualSetupKey }}');
                                                this.copied = true;
                                                setTimeout(() => this.copied = false, 1500);
                                            } catch (e) {
                                                console.warn('Could not copy to clipboard');
                                            }
                                        }
                                    }"
                                >
                                    <div class="join w-full">
                                        @empty($manualSetupKey)
                                            <div class="join-item w-full p-3 bg-base-200 text-center">
                                                <span class="loading loading-spinner loading-sm"></span>
                                            </div>
                                        @else
                                            <input
                                                type="text"
                                                readonly
                                                value="{{ $manualSetupKey }}"
                                                class="input input-bordered join-item w-full"
                                            />

                                            <button @click="copy()" type="button" class="btn join-item">
                                                <span x-show="!copied">Copy</span>
                                                <span x-show="copied">Done</span>
                                            </button>
                                        @endempty
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end">
                                <button class="btn btn-ghost" wire:click="closeModal" type="button">{{ __('Close') }}</button>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        @endif
    </x-settings.layout>
</section>
