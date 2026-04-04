<div class="mx-auto max-w-5xl p-4 sm:p-6 lg:p-8">
    <div class="mb-6">
        <h1 class="text-2xl font-bold sm:text-3xl">{{ __('Backup & Restore') }}</h1>
        <p class="mt-2 text-base-content/60">{{ __('Backup und Wiederherstellung für Playlists, Tracks, RFID-Mappings und Player-Settings.') }}</p>
    </div>

    @if($statusMessage)
        <div role="alert" class="alert mb-4 {{ $statusType === 'error' ? 'alert-error' : 'alert-success' }}">
            <span>{{ $statusMessage }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <div class="card border border-base-300 bg-base-200">
            <div class="card-body">
                <h2 class="card-title">{{ __('Create Backup') }}</h2>
                <p class="text-sm text-base-content/70">{{ __('Erzeugt ein ZIP in storage/app/private/backups inklusive Audiodateien.') }}</p>

                <div class="card-actions mt-3 justify-end">
                    <button wire:click="createBackup" class="btn btn-primary" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="createBackup">{{ __('Backup now') }}</span>
                        <span wire:loading wire:target="createBackup" class="loading loading-spinner loading-sm"></span>
                    </button>
                </div>
            </div>
        </div>

        <div class="card border border-base-300 bg-base-200">
            <div class="card-body">
                <h2 class="card-title">{{ __('Restore Options') }}</h2>
                <label class="label cursor-pointer justify-start gap-3">
                    <input type="checkbox" class="checkbox" wire:model="appendMode" />
                    <span class="label-text">{{ __('Append mode (existing playlists behalten)') }}</span>
                </label>
                <p class="text-sm text-base-content/70">
                    {{ __('Wenn deaktiviert, werden vorhandene Playlists vor dem Restore ersetzt.') }}
                </p>
            </div>
        </div>
    </div>

    <div class="mt-6 card border border-base-300 bg-base-100">
        <div class="card-body p-0">
            <div class="overflow-x-auto">
                <table class="table table-zebra">
                    <thead>
                        <tr>
                            <th>{{ __('Backup file') }}</th>
                            <th>{{ __('Size') }}</th>
                            <th>{{ __('Updated') }}</th>
                            <th class="text-right">{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->backups as $backup)
                            <tr>
                                <td class="font-medium">{{ $backup['name'] }}</td>
                                <td>{{ number_format($backup['size'] / 1024, 1) }} KB</td>
                                <td>{{ $backup['updated_at'] }}</td>
                                <td class="text-right">
                                    <button
                                        wire:click="restoreBackup('{{ $backup['name'] }}')"
                                        wire:confirm="{{ __('Restore this backup now?') }}"
                                        class="btn btn-sm btn-outline"
                                        wire:loading.attr="disabled"
                                    >
                                        {{ __('Restore') }}
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-base-content/60">{{ __('No backups found yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
