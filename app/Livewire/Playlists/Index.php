<?php

namespace App\Livewire\Playlists;

use App\Models\Playlist;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Index extends Component
{
    #[Computed]
    public function playlists()
    {
        return Playlist::with('tracks')->latest()->get();
    }

    public function deletePlaylist(int $playlistId): void
    {
        $playlist = Playlist::findOrFail($playlistId);
        $name = $playlist->name;

        $playlist->delete();

        session()->flash('message', "Playlist '{$name}' deleted successfully!");
    }

    public function render()
    {
        return view('livewire.playlists.index');
    }
}
