<section class="w-full">
    @include('partials.settings-heading')

    <h2 class="sr-only">{{ __('Wi-Fi settings') }}</h2>

    <x-settings.layout :heading="__('Wi-Fi')" :subheading="__('Manage known Wi-Fi networks and automatic hotspot fallback')">
        <div class="space-y-6 mt-6">
            <div class="card bg-base-100 border border-base-300">
                <div class="card-body p-4">
                    <div class="flex items-center justify-between gap-3 flex-wrap">
                        <div>
                            <h3 class="font-semibold">{{ __('Runtime status') }}</h3>
                            <p class="text-sm opacity-70">{{ __('Current connection and fallback mode') }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            @if($nmcliAvailable)
                                <span class="badge badge-success">nmcli</span>
                            @else
                                <span class="badge badge-error">nmcli {{ __('missing') }}</span>
                            @endif

                            @if($hotspotActive)
                                <span class="badge badge-warning">{{ __('Hotspot active') }}</span>
                            @else
                                <span class="badge">{{ __('Hotspot inactive') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="mt-3 text-sm space-y-1">
                        <p>
                            <span class="font-medium">{{ __('Current Wi-Fi') }}:</span>
                            <span>{{ $currentSsid ?? __('Not connected') }}</span>
                        </p>
                        <p>
                            <span class="font-medium">{{ __('Fallback hotspot SSID') }}:</span>
                            <span>{{ $hotspotSsid }}</span>
                        </p>
                    </div>

                    <div class="mt-4 flex items-center gap-2">
                        <button type="button" class="btn btn-sm btn-primary" wire:click="runWifiManager">{{ __('Run auto-switch now') }}</button>
                        <button type="button" class="btn btn-sm btn-ghost" wire:click="refreshRuntimeStatus">{{ __('Refresh status') }}</button>
                    </div>

                    @if($lastRunMessage !== '')
                        <div class="alert alert-soft mt-3">
                            <span class="text-sm">{{ $lastRunMessage }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card bg-base-100 border border-base-300">
                <div class="card-body p-4">
                    <h3 class="font-semibold">{{ $editingNetworkId ? __('Edit Wi-Fi') : __('Add Wi-Fi') }}</h3>

                    <form wire:submit="saveNetwork" class="space-y-4 mt-3">
                        <fieldset class="fieldset">
                            <legend class="fieldset-legend">SSID</legend>
                            <input wire:model="ssid" type="text" class="input input-bordered w-full" required />
                            @error('ssid')
                                <label class="label"><span class="label-text-alt text-error">{{ $message }}</span></label>
                            @enderror
                        </fieldset>

                        <fieldset class="fieldset">
                            <legend class="fieldset-legend">{{ __('Password (optional)') }}</legend>
                            <input wire:model="password" type="password" class="input input-bordered w-full" autocomplete="new-password" />
                            @error('password')
                                <label class="label"><span class="label-text-alt text-error">{{ $message }}</span></label>
                            @enderror
                        </fieldset>

                        <fieldset class="fieldset">
                            <legend class="fieldset-legend">{{ __('Priority') }}</legend>
                            <input wire:model="priority" type="number" class="input input-bordered w-full" min="-100" max="100" />
                            <p class="label">{{ __('Higher values are preferred first.') }}</p>
                            @error('priority')
                                <label class="label"><span class="label-text-alt text-error">{{ $message }}</span></label>
                            @enderror
                        </fieldset>

                        <label class="label cursor-pointer justify-start gap-3">
                            <input wire:model="auto_connect" type="checkbox" class="checkbox" />
                            <span class="label-text">{{ __('Use for auto-connect') }}</span>
                        </label>

                        <div class="flex items-center gap-2">
                            <button type="submit" class="btn btn-primary btn-sm">{{ __('Save network') }}</button>
                            @if($editingNetworkId)
                                <button type="button" class="btn btn-ghost btn-sm" wire:click="cancelEdit">{{ __('Cancel') }}</button>
                            @endif
                            <x-action-message on="wifi-network-saved">{{ __('Saved.') }}</x-action-message>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card bg-base-100 border border-base-300">
                <div class="card-body p-4">
                    <h3 class="font-semibold">{{ __('Saved Wi-Fi networks') }}</h3>

                    @if($this->networks->isEmpty())
                        <p class="text-sm opacity-70 mt-3">{{ __('No Wi-Fi networks saved yet.') }}</p>
                    @else
                        <ul class="list mt-3">
                            @foreach($this->networks as $network)
                                <li class="list-row" wire:key="wifi-network-{{ $network->id }}">
                                    <div class="list-col-grow">
                                        <div class="font-medium">{{ $network->ssid }}</div>
                                        <div class="text-xs opacity-70">
                                            {{ __('Priority') }}: {{ $network->priority }}
                                            ·
                                            {{ $network->auto_connect ? __('Auto-connect enabled') : __('Auto-connect disabled') }}
                                        </div>
                                    </div>

                                    <button type="button" class="btn btn-ghost btn-xs" wire:click="editNetwork({{ $network->id }})">{{ __('Edit') }}</button>
                                    <button type="button" class="btn btn-ghost btn-xs text-error" wire:click="deleteNetwork({{ $network->id }})">{{ __('Delete') }}</button>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </x-settings.layout>
</section>
