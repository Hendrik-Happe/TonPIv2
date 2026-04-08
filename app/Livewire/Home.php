<?php

namespace App\Livewire;

use App\Models\Playlist;
use App\Models\Tag;
use App\Services\PlayerManager;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class Home extends Component
{
    use WithPagination;

    public string $search = '';

    public string $tagFilter = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingTagFilter(): void
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
            ->with('tags')
            ->withCount('tracks')
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($subQuery): void {
                    $subQuery->where('name', 'like', '%'.$this->search.'%')
                        ->orWhereHas('tags', function ($tagsQuery): void {
                            $tagsQuery->where('name', 'like', '%'.$this->search.'%');
                        });
                });
            })
            ->when($this->tagFilter !== '', function ($query): void {
                $query->whereHas('tags', function ($tagsQuery): void {
                    $tagsQuery->whereKey((int) $this->tagFilter);
                });
            })
            ->latest()
            ->paginate(12);
    }

    #[Computed]
    public function availableTags()
    {
        return Tag::query()->orderBy('name')->get();
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
