<?php

namespace App\Livewire;

use App\Models\Playlist;
use App\Services\PlayerManager;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Home extends Component
{
    #[Computed]
    public function playlists()
    {
        return Playlist::withCount('tracks')->latest()->get();
    }

    public function playPlaylist(int $playlistId): void
    {
        $playlist = Playlist::findOrFail($playlistId);
        $playerManager = app(PlayerManager::class);
        $playerManager->playPlaylist($playlist, 'ui', 'home-card');

        $this->dispatch('playlist-started');
    }

    public function render()
    {
        return view('livewire.home');
    }
}
