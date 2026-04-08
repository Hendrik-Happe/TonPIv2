<div class="container mx-auto p-4 sm:p-6 lg:p-8 max-w-7xl" wire:poll.2s>
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold">{{ __('Your Playlists') }}</h1>
            @if($this->playerState->rfid_chip_present)
                <div class="mt-2 badge badge-success gap-2">
                    <span class="status status-success"></span>
                    {{ __('RFID chip present') }}
                    @if($this->playerState->present_rfid_uid)
                        ({{ $this->playerState->present_rfid_uid }})
                    @endif
                </div>
            @else
                <div class="mt-2 badge badge-ghost gap-2">
                    <span class="status status-neutral"></span>
                    {{ __('No RFID chip present') }}
                </div>
            @endif
        </div>
        @auth
        <a href="/playlists/create" wire:navigate class="btn btn-primary btn-sm sm:btn-md">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            {{ __('Create') }}
        </a>
        @endauth
    </div>

    <div class="mb-6 grid grid-cols-1 gap-3 md:grid-cols-3">
        <label class="input input-bordered md:col-span-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input
                type="text"
                class="grow"
                placeholder="{{ __('Search playlists or tags...') }}"
                wire:model.live.debounce.300ms="search"
            />
        </label>

        <select class="select select-bordered w-full" wire:model.live="tagFilter">
            <option value="">{{ __('All tags') }}</option>
            @foreach($this->availableTags as $tag)
                <option value="{{ $tag->id }}">{{ $tag->name }}</option>
            @endforeach
        </select>
    </div>

    @if($this->playlists->isEmpty())
        <div class="hero min-h-[400px] bg-base-200 rounded-box">
            <div class="hero-content text-center">
                <div class="max-w-md">
                    <h2 class="text-3xl sm:text-5xl font-bold">🎵</h2>
                    <h3 class="text-xl sm:text-2xl font-bold mt-4">
                        {{ $search !== '' ? __('No playlists found') : __('No playlists yet') }}
                    </h3>
                    <p class="py-6">
                        {{ $search !== '' ? __('Try a different search term.') : __('Create your first playlist') }}
                    </p>
                    @auth
                    <a href="/playlists/create" wire:navigate class="btn btn-primary">
                        {{ __('Create New Playlist') }}
                    </a>
                    @endauth
                </div>
            </div>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-6">
            @foreach($this->playlists as $playlist)
                <div class="card bg-base-200 shadow-xl hover:shadow-2xl transition-shadow">
                    @if($playlist->cover_path)
                        <figure class="px-4 pt-4 sm:px-5 sm:pt-5">
                            <img src="{{ asset('storage/'.$playlist->cover_path) }}" alt="{{ $playlist->name }} cover" class="h-40 w-full rounded-box bg-base-300/30 object-contain p-1" />
                        </figure>
                    @endif
                    <div class="card-body p-4 sm:p-5">
                        <h2 class="card-title text-lg sm:text-xl truncate">{{ $playlist->name }}</h2>
                        <p class="text-sm opacity-60">
                            {{ $playlist->tracks_count }} {{ __('tracks') }}
                        </p>
                        @if($playlist->tags->isNotEmpty())
                            <div class="mt-2 flex flex-wrap gap-1">
                                @foreach($playlist->tags as $tag)
                                    <span class="badge badge-outline badge-sm">{{ $tag->name }}</span>
                                @endforeach
                            </div>
                        @endif
                        
                        <div class="card-actions justify-end mt-4 gap-2">
                            <button 
                                wire:click="playPlaylist({{ $playlist->id }})" 
                                class="btn btn-primary btn-sm sm:btn-md flex-1 sm:flex-none"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:h-5 sm:w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="hidden sm:inline">{{ __('Play') }}</span>
                            </button>
                            
                            @auth
                            <a 
                                href="/playlists/{{ $playlist->id }}/edit" 
                                wire:navigate 
                                class="btn btn-ghost btn-sm sm:btn-md"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:h-5 sm:w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2  2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </a>
                            @endauth
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $this->playlists->links() }}
        </div>
    @endif
</div>
