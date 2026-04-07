<?php

namespace App\Livewire;

use App\Actions\Player\PlayPlaylist;
use App\Actions\Player\PreviousTrack;
use App\Actions\Player\SetVolume;
use App\Actions\Player\SkipTrack;
use App\Actions\Player\TogglePlayPause;
use App\Models\Playlist;
use App\Services\PlayerManager;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Remote Control')]
class RemoteControl extends Component
{
    use WithPagination;

    public ?int $selectedPlaylistId = null;

    public int $volumePercentage = 100;

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function mount(): void
    {
        $this->syncFromPlayerState();
    }

    public function syncFromPlayerState(): void
    {
        $state = app(PlayerManager::class)->getState();

        $this->selectedPlaylistId = $state->current_playlist_id;
        $this->volumePercentage = (int) ($state->volume_percentage ?? 100);
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
            ->orderBy('name')
            ->paginate(12);
    }

    #[Computed]
    public function currentTrack()
    {
        return $this->playerState->currentTrack;
    }

    #[Computed]
    public function currentPlaylist()
    {
        return $this->playerState->currentPlaylist;
    }

    public function playPlaylist(int $playlistId): void
    {
        $playlist = Playlist::query()->find($playlistId);

        if ($playlist === null) {
            return;
        }

        app(PlayPlaylist::class)->execute($playlist);
        $this->selectedPlaylistId = $playlistId;
    }

    public function togglePlayPause(): void
    {
        app(TogglePlayPause::class)->execute();
    }

    public function next(): void
    {
        app(SkipTrack::class)->execute();
    }

    public function previous(): void
    {
        app(PreviousTrack::class)->execute();
    }

    public function updatedVolumePercentage(int|string $value): void
    {
        app(SetVolume::class)->execute((int) $value);
    }

    public function render()
    {
        return view('livewire.remote-control');
    }
}
