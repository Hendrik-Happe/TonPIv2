<?php

namespace App\Livewire;

use App\Models\Playlist;
use App\Services\PlayerManager;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class Home extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function playerState()
    {
        return app(PlayerManager::class)->getState();
    }

    #[Computed]
    public function playlists()
    {
        return Playlist::query()
            ->withCount('tracks')
            ->when($this->search !== '', function ($query): void {
                $query->where('name', 'like', '%'.$this->search.'%');
            })
            ->latest()
            ->paginate(12);
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
