<?php

namespace App\Livewire;

use App\Actions\Player\PlayPlaylist;
use App\Actions\Player\PreviousTrack;
use App\Actions\Player\SetRepeatMode;
use App\Actions\Player\SetVolume;
use App\Actions\Player\SkipTrack;
use App\Actions\Player\TogglePlayPause;
use App\Models\Playlist;
use App\Services\PlayerManager;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Player extends Component
{
    public ?int $selectedPlaylistId = null;

    public int $volumePercentage = 100;

    public function mount(): void
    {
        $playerManager = app(PlayerManager::class);
        $state = $playerManager->getState();
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
        return Playlist::with('tracks')->get();
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
        $playlist = Playlist::find($playlistId);

        if ($playlist) {
            app(PlayPlaylist::class)->execute($playlist);
            $this->selectedPlaylistId = $playlistId;
        }
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

    public function setRepeatMode(string $mode): void
    {
        app(SetRepeatMode::class)->execute($mode);
    }

    public function updatedVolumePercentage(int|string $value): void
    {
        app(SetVolume::class)->execute((int) $value);
    }

    public function render()
    {
        return view('livewire.player');
    }
}
