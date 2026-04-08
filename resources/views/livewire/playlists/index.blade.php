<div class="max-w-6xl mx-auto p-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold">Playlists</h1>
        <a href="/playlists/create" wire:navigate class="btn btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Create New Playlist
        </a>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-3 md:grid-cols-3">
        <label class="input input-bordered md:col-span-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input
                type="text"
                class="grow"
                placeholder="{{ __('Search playlists, RFID or tags...') }}"
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

    @if (session('message'))
        <div role="alert" class="alert alert-success mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 shrink-0 stroke-current" fill="none" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('message') }}</span>
        </div>
    @endif

    @if($this->playlists->isEmpty())
        <div class="text-center py-12">
            <div class="opacity-40 mb-4">
                <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path>
                </svg>
            </div>
            @if($search !== '')
                <h2 class="text-2xl font-bold mb-2">{{ __('No playlists found') }}</h2>
                <p class="opacity-60 mb-4">{{ __('Try a different search term.') }}</p>
            @else
                <h2 class="text-2xl font-bold mb-2">No playlists yet</h2>
                <p class="opacity-60 mb-4">
                    Create your first playlist to get started.
                </p>
                <a href="/playlists/create" wire:navigate class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Create Playlist
                </a>
            @endif
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($this->playlists as $playlist)
                <div 
                    wire:key="playlist-{{ $playlist->id }}"
                    class="card bg-base-100 border border-base-300 shadow-sm hover:shadow-lg transition"
                >
                    @if($playlist->cover_path)
                        <figure class="px-5 pt-5">
                            <img src="{{ asset('storage/'.$playlist->cover_path) }}" alt="{{ $playlist->name }} cover" class="h-40 w-full rounded-box bg-base-300/30 object-contain p-1" />
                        </figure>
                    @endif
                    <div class="card-body p-5">
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex-1">
                                <h3 class="text-xl font-bold mb-1">
                                    {{ $playlist->name }}
                                </h3>
                                <p class="text-sm opacity-60">
                                    {{ $playlist->tracks->count() }} {{ __('tracks') }}
                                </p>
                                <p class="text-xs mt-1 opacity-70">
                                    {{ __('RFID') }}: {{ $playlist->rfid_uid ?: __('not linked') }}
                                </p>
                                @if($playlist->tags->isNotEmpty())
                                    <div class="mt-2 flex flex-wrap gap-1">
                                        @foreach($playlist->tags as $tag)
                                            <span class="badge badge-outline badge-sm">{{ $tag->name }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                            
                            <div class="dropdown dropdown-end self-center">
                                <div tabindex="0" role="button" class="btn btn-ghost btn-sm btn-circle place-items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                                    </svg>
                                </div>
                                <ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-box z-[1] w-52 p-2 shadow border border-base-300">
                                    <li>
                                        <a href="/playlists/{{ $playlist->id }}/edit" wire:navigate class="flex items-center gap-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                            Edit
                                        </a>
                                    </li>
                                    <li>
                                        <button wire:click="deletePlaylist({{ $playlist->id }})" class="text-error flex items-center gap-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            Delete
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        @if($playlist->tracks->isNotEmpty())
                            <div class="space-y-1 mt-4">
                                @foreach($playlist->tracks->take(3) as $track)
                                    <div class="text-sm opacity-80 flex items-center gap-2">
                                        <span class="opacity-60">{{ $track->track_number }}.</span>
                                        <span class="truncate">{{ $track->title }}</span>
                                    </div>
                                @endforeach
                                @if($playlist->tracks->count() > 3)
                                    <div class="text-sm opacity-60 italic">
                                        +{{ $playlist->tracks->count() - 3 }} more
                                    </div>
                                @endif
                            </div>
                        @else
                            <p class="text-sm opacity-60 italic mt-4">No tracks</p>
                        @endif

                        <div class="mt-4">
                            <button
                                type="button"
                                class="btn btn-outline btn-sm"
                                wire:click="learnRfidForPlaylist({{ $playlist->id }})"
                                wire:loading.attr="disabled"
                            >
                                <span wire:loading.remove wire:target="learnRfidForPlaylist({{ $playlist->id }})">{{ __('Chip anlernen') }}</span>
                                <span wire:loading wire:target="learnRfidForPlaylist({{ $playlist->id }})" class="loading loading-spinner loading-sm"></span>
                            </button>

                            @if ($rfidLearningPlaylistId === $playlist->id && $rfidLearningFeedback)
                                <p class="text-xs mt-2 text-base-content/70">{{ $rfidLearningFeedback }}</p>
                            @endif
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
