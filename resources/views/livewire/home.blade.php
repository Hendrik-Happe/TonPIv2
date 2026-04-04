<div class="container mx-auto p-4 sm:p-6 lg:p-8 max-w-7xl">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <h1 class="text-2xl sm:text-3xl font-bold">{{ __('Your Playlists') }}</h1>
        <a href="/playlists/create" wire:navigate class="btn btn-primary btn-sm sm:btn-md">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            {{ __('Create') }}
        </a>
    </div>

    @if($this->playlists->isEmpty())
        <div class="hero min-h-[400px] bg-base-200 rounded-box">
            <div class="hero-content text-center">
                <div class="max-w-md">
                    <h2 class="text-3xl sm:text-5xl font-bold">🎵</h2>
                    <h3 class="text-xl sm:text-2xl font-bold mt-4">{{ __('No playlists yet') }}</h3>
                    <p class="py-6">{{ __('Create your first playlist') }}</p>
                    <a href="/playlists/create" wire:navigate class="btn btn-primary">
                        {{ __('Create New Playlist') }}
                    </a>
                </div>
            </div>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-6">
            @foreach($this->playlists as $playlist)
                <div class="card bg-base-200 shadow-xl hover:shadow-2xl transition-shadow">
                    <div class="card-body p-4 sm:p-5">
                        <h2 class="card-title text-lg sm:text-xl truncate">{{ $playlist->name }}</h2>
                        <p class="text-sm opacity-60">
                            {{ $playlist->tracks_count }} {{ __('tracks') }}
                        </p>
                        
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
                            
                            <a 
                                href="/playlists/{{ $playlist->id }}/edit" 
                                wire:navigate 
                                class="btn btn-ghost btn-sm sm:btn-md"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:h-5 sm:w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2  2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
