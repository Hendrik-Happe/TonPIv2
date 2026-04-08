<div class="bg-base-100 border-t border-base-300 p-3 sm:p-4" wire:poll.2s="syncFromPlayerState">
    <div class="max-w-7xl mx-auto">
        @if($this->currentPlaylist)
            <div class="flex flex-col gap-3">
                <!-- Now Playing Info -->
                <div class="flex items-center justify-between gap-2">
                    <div class="flex min-w-0 flex-1 items-center gap-3">
                        @if($this->currentPlaylist->cover_path)
                            <img
                                src="{{ asset('storage/'.$this->currentPlaylist->cover_path) }}"
                                alt="{{ $this->currentPlaylist->name }} cover"
                                class="h-12 w-12 rounded-box bg-base-300/30 object-contain p-0.5"
                            />
                        @endif

                        <div class="min-w-0 flex-1">
                            <div class="text-xs sm:text-sm font-semibold opacity-60 truncate">
                                {{ $this->currentPlaylist->name }}
                            </div>
                            @if($this->currentTrack)
                                <div class="text-sm sm:text-base font-bold truncate">
                                    {{ $this->currentTrack->title }}
                                </div>
                                <div class="text-xs opacity-60">
                                    {{ __('Track') }} {{ $this->playerState->current_position + 1 }} {{ __('of') }} {{ $this->currentPlaylist->tracks->count() }}
                                </div>
                            @else
                                <div class="text-sm opacity-60">{{ __('No track playing') }}</div>
                            @endif
                        </div>
                    </div>

                    <!-- Status Badge -->
                    <div class="hidden sm:block">
                        @if($this->playerState->status === 'playing')
                            <span class="badge badge-success badge-sm">{{ __('Playing') }}</span>
                        @elseif($this->playerState->status === 'paused')
                            <span class="badge badge-warning badge-sm">{{ __('Paused') }}</span>
                        @else
                            <span class="badge badge-sm">{{ __('Stopped') }}</span>
                        @endif
                    </div>
                </div>

                <!-- Player Controls -->
                <div class="flex items-center justify-between gap-2">
                    <div class="flex items-center gap-1 sm:gap-2">
                        <!-- Previous Button -->
                        <button wire:click="previous" class="btn btn-ghost btn-sm btn-circle">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:h-5 sm:w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0019 16V8a1 1 0 00-1.6-.8l-5.333 4zM4.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0011 16V8a1 1 0 00-1.6-.8l-5.334 4z" />
                            </svg>
                        </button>

                        <!-- Play/Pause Button -->
                        <button wire:click="togglePlayPause" class="btn btn-primary btn-sm sm:btn-md">
                            @if($this->playerState->status === 'playing')
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 sm:h-6 sm:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            @else
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 sm:h-6 sm:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            @endif
                        </button>

                        <!-- Next Button -->
                        <button wire:click="next" class="btn btn-ghost btn-sm btn-circle">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:h-5 sm:w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.933 12.8a1 1 0 000-1.6L6.6 7.2A1 1 0 005 8v8a1 1 0 001.6.8l5.333-4zM19.933 12.8a1 1 0 000-1.6l-5.333-4A1 1 0 0013 8v8a1 1 0 001.6.8l5.333-4z" />
                            </svg>
                        </button>
                    </div>

                    <!-- Repeat Mode Controls -->
                    <div class="flex items-center gap-1">
                        <span class="text-xs opacity-60 hidden sm:inline">{{ __('Repeat') }}:</span>
                        <button 
                            wire:click="setRepeatMode('none')" 
                            class="btn btn-xs {{ $this->playerState->repeat_mode === 'none' ? 'btn-primary' : 'btn-ghost' }}"
                        >
                            {{ __('Off') }}
                        </button>
                        <button 
                            wire:click="setRepeatMode('one')" 
                            class="btn btn-xs {{ $this->playerState->repeat_mode === 'one' ? 'btn-primary' : 'btn-ghost' }}"
                        >
                            {{ __('One') }}
                        </button>
                        <button 
                            wire:click="setRepeatMode('all')" 
                            class="btn btn-xs {{ $this->playerState->repeat_mode === 'all' ? 'btn-primary' : 'btn-ghost' }}"
                        >
                            {{ __('All') }}
                        </button>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <span class="text-xs opacity-60 min-w-20">{{ __('Volume') }}:</span>
                    <input
                        type="range"
                        min="0"
                        max="100"
                        step="1"
                        class="range range-sm range-primary"
                        wire:model.live.debounce.150ms="volumePercentage"
                    />
                    <span class="text-xs font-semibold w-10 text-right">{{ $volumePercentage }}%</span>
                </div>
            </div>
        @else
            <div class="text-center py-2 sm:py-4 opacity-60 text-sm">
                {{ __('No playlist selected') }}
            </div>
        @endif
    </div>
</div>

