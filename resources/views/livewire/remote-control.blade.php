<div class="mx-auto max-w-4xl p-4 sm:p-6 lg:p-8" wire:poll.2s="syncFromPlayerState">
    <div class="mb-6 flex items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold sm:text-3xl">{{ __('Remote Control') }}</h1>
            <p class="mt-1 text-sm text-base-content/60">{{ __('Steuere die Wiedergabe direkt vom Handy oder Browser.') }}</p>
        </div>
        @if($this->playerState->status === 'playing')
            <span class="badge badge-success">{{ __('Playing') }}</span>
        @elseif($this->playerState->status === 'paused')
            <span class="badge badge-warning">{{ __('Paused') }}</span>
        @else
            <span class="badge">{{ __('Stopped') }}</span>
        @endif
    </div>

    <div class="card border border-base-300 bg-base-200">
        <div class="card-body p-4 sm:p-6">
            @if($this->currentPlaylist)
                <p class="text-xs uppercase tracking-wide text-base-content/60">{{ __('Now playing') }}</p>
                <p class="text-lg font-semibold sm:text-xl">{{ $this->currentPlaylist->name }}</p>
                <p class="text-sm text-base-content/70">
                    {{ $this->currentTrack?->title ?? __('No track playing') }}
                </p>
            @else
                <p class="text-sm text-base-content/70">{{ __('No playlist selected') }}</p>
            @endif

            <div class="mt-5 flex items-center justify-center gap-3 sm:gap-4">
                <button wire:click="previous" class="btn btn-circle btn-lg sm:btn-xl btn-ghost" aria-label="{{ __('Previous') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0019 16V8a1 1 0 00-1.6-.8l-5.333 4zM4.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0011 16V8a1 1 0 00-1.6-.8l-5.334 4z" />
                    </svg>
                </button>

                <button wire:click="togglePlayPause" class="btn btn-circle btn-xl sm:btn-xl btn-primary" aria-label="{{ __('Play or pause') }}">
                    @if($this->playerState->status === 'playing')
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    @else
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    @endif
                </button>

                <button wire:click="next" class="btn btn-circle btn-lg sm:btn-xl btn-ghost" aria-label="{{ __('Next') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.933 12.8a1 1 0 000-1.6L6.6 7.2A1 1 0 005 8v8a1 1 0 001.6.8l5.333-4zM19.933 12.8a1 1 0 000-1.6l-5.333-4A1 1 0 0013 8v8a1 1 0 001.6.8l5.333-4z" />
                    </svg>
                </button>
            </div>

            <div class="mt-6">
                <div class="mb-2 flex items-center justify-between text-sm">
                    <span class="text-base-content/70">{{ __('Volume') }}</span>
                    <span class="font-semibold">{{ $volumePercentage }}%</span>
                </div>
                <input
                    type="range"
                    min="0"
                    max="100"
                    step="1"
                    class="range range-primary range-lg"
                    wire:model.live.debounce.150ms="volumePercentage"
                />
            </div>
        </div>
    </div>

    <div class="mt-6">
        <h2 class="mb-3 text-lg font-semibold">{{ __('Playlists') }}</h2>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            @foreach($this->playlists as $playlist)
                <button
                    wire:click="playPlaylist({{ $playlist->id }})"
                    class="btn h-auto min-h-0 justify-between rounded-box border-base-300 px-4 py-4 text-left normal-case {{ $this->selectedPlaylistId === $playlist->id ? 'btn-primary' : 'btn-outline' }}"
                >
                    <span class="truncate">{{ $playlist->name }}</span>
                    <span class="text-xs opacity-80">{{ $playlist->tracks_count }} {{ __('tracks') }}</span>
                </button>
            @endforeach
        </div>
    </div>
</div>
