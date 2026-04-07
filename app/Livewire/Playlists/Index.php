<?php

namespace App\Livewire\Playlists;

use App\Models\Playlist;
use App\Services\RfidReader;
use App\Services\RfidTagNormalizer;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use RuntimeException;

class Index extends Component
{
    use WithPagination;

    public ?int $rfidLearningPlaylistId = null;

    public ?string $rfidLearningFeedback = null;

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function playlists()
    {
        return Playlist::query()
            ->with('tracks')
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($subQuery): void {
                    $subQuery->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('rfid_uid', 'like', '%'.$this->search.'%');
                });
            })
            ->latest()
            ->paginate(12);
    }

    public function deletePlaylist(int $playlistId): void
    {
        $playlist = Playlist::findOrFail($playlistId);
        $name = $playlist->name;

        $playlist->delete();

        session()->flash('message', "Playlist '{$name}' deleted successfully!");
    }

    public function learnRfidForPlaylist(int $playlistId, RfidReader $rfidReader, RfidTagNormalizer $normalizer): void
    {
        $playlist = Playlist::query()->findOrFail($playlistId);

        try {
            $rawUid = $rfidReader->readSingleUid();
        } catch (RuntimeException $exception) {
            $this->rfidLearningPlaylistId = $playlist->id;
            $this->rfidLearningFeedback = $exception->getMessage();

            return;
        }

        if ($rawUid === null) {
            $this->rfidLearningPlaylistId = $playlist->id;
            $this->rfidLearningFeedback = 'Kein RFID-Chip erkannt.';

            return;
        }

        $normalizedUid = $normalizer->normalize($rawUid);

        if ($normalizedUid === null) {
            $this->rfidLearningPlaylistId = $playlist->id;
            $this->rfidLearningFeedback = 'RFID-Chip gelesen, aber UID ist ungültig.';

            return;
        }

        Playlist::query()
            ->where('rfid_uid', $normalizedUid)
            ->whereKeyNot($playlist->id)
            ->update(['rfid_uid' => null]);

        $playlist->update(['rfid_uid' => $normalizedUid]);

        $this->rfidLearningPlaylistId = $playlist->id;
        $this->rfidLearningFeedback = sprintf('RFID UID %s wurde mit "%s" verknüpft.', $normalizedUid, $playlist->name);
    }

    public function render()
    {
        return view('livewire.playlists.index');
    }
}
