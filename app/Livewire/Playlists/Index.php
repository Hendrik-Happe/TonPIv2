<?php

namespace App\Livewire\Playlists;

use App\Models\Playlist;
use App\Models\Tag;
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
    public function playlists()
    {
        return Playlist::query()
            ->with(['tracks', 'tags'])
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($subQuery): void {
                    $subQuery->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('rfid_uid', 'like', '%'.$this->search.'%')
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
