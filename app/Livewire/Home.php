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

    /** @var array<int> */
    public array $tagFilter = [];

    public string $tagSearch = '';

    public bool $showTagDropdown = false;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingTagFilter(): void
    {
        $this->resetPage();
    }

    public function updatingTagSearch(): void
    {
        $this->showTagDropdown = true;
    }

    public function toggleTag(int $tagId): void
    {
        if (in_array($tagId, $this->tagFilter, true)) {
            $this->removeTag($tagId);
        } else {
            $this->tagFilter[] = $tagId;
            sort($this->tagFilter);
            $this->resetPage();
        }
    }

    public function removeTag(int $tagId): void
    {
        $this->tagFilter = array_filter($this->tagFilter, fn ($id) => $id !== $tagId);
        $this->tagFilter = array_values($this->tagFilter);
        $this->resetPage();
    }

    public function clearTagFilter(): void
    {
        $this->tagFilter = [];
        $this->tagSearch = '';
        $this->showTagDropdown = false;
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
            ->when(! empty($this->tagFilter), function ($query): void {
                foreach ($this->tagFilter as $tagId) {
                    $query->whereHas('tags', function ($tagsQuery) use ($tagId): void {
                        $tagsQuery->whereKey((int) $tagId);
                    });
                }
            })
            ->latest()
            ->paginate(12);
    }

    #[Computed]
    public function availableTags()
    {
        return Tag::query()->orderBy('name')->get();
    }

    #[Computed]
    public function filteredTags()
    {
        return Tag::query()
            ->when($this->tagSearch !== '', function ($query): void {
                $query->where('name', 'like', '%'.$this->tagSearch.'%');
            })
            ->orderBy('name')
            ->get();
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
