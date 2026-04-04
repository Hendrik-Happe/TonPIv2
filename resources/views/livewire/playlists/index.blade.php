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
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($this->playlists as $playlist)
                <div 
                    wire:key="playlist-{{ $playlist->id }}"
                    class="card bg-base-100 border border-base-300 shadow-sm hover:shadow-lg transition"
                >
                    <div class="card-body p-5">
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex-1">
                                <h3 class="text-xl font-bold mb-1">
                                    {{ $playlist->name }}
                                </h3>
                                <p class="text-sm opacity-60">
                                    {{ $playlist->tracks->count() }} {{ __('tracks') }}
                                </p>
                            </div>
                            
                            <div class="dropdown dropdown-end">
                                <div tabindex="0" role="button" class="btn btn-ghost btn-sm btn-circle">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                                    </svg>
                                </div>
                                <ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-box z-[1] w-52 p-2 shadow border border-base-300">
                                    <li>
                                        <button wire:click="deletePlaylist({{ $playlist->id }})" class="text-error">
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
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
