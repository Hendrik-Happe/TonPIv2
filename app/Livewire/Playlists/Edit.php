<?php

namespace App\Livewire\Playlists;

use App\Models\Playlist;
use App\Models\Tag;
use App\Models\Track;
use App\Services\RfidReader;
use App\Services\RfidTagNormalizer;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

class Edit extends Component
{
    use WithFileUploads;

    public Playlist $playlist;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|string|max:255')]
    public string $rfidUid = '';

    #[Validate('nullable|integer|min:0|max:100')]
    public int|string|null $volumeProfile = null;

    #[Validate('nullable|string|max:500')]
    public string $tags = '';

    public ?string $rfidReadFeedback = null;

    public array $tracks = [];

    public array $uploadedFiles = [];

    public $coverImage;

    public bool $removeCoverImage = false;

    public function mount(Playlist $playlist): void
    {
        $this->playlist = $playlist;
        $this->name = $playlist->name;
        $this->rfidUid = $playlist->rfid_uid ?? '';
        $this->volumeProfile = $playlist->volume_profile;
        $this->tags = $playlist->tags()->orderBy('name')->pluck('name')->implode(', ');

        // Load existing tracks
        foreach ($playlist->tracks as $track) {
            $this->tracks[] = [
                'id' => $track->id,
                'title' => $track->title,
                'file_name' => basename($track->file_path),
                'duration' => $track->duration,
                'track_number' => $track->track_number,
                'existing' => true,
            ];
        }
    }

    public function updatedUploadedFiles(): void
    {
        $this->validate([
            'uploadedFiles.*' => 'mimes:mp3,wav,ogg,flac,m4a,aac,wma|max:102400',
        ]);

        foreach ($this->uploadedFiles as $file) {
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $tempPath = $file->getRealPath();
            $duration = $this->extractDuration($tempPath);

            $this->tracks[] = [
                'id' => uniqid(),
                'title' => $originalName,
                'file' => $file,
                'file_name' => $file->getClientOriginalName(),
                'duration' => $duration,
                'track_number' => count($this->tracks) + 1,
                'existing' => false,
            ];
        }

        $this->reset('uploadedFiles');
    }

    private function extractDuration(string $filePath): ?int
    {
        try {
            $command = sprintf(
                'ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 %s 2>&1',
                escapeshellarg($filePath)
            );

            $output = shell_exec($command);

            if ($output !== null && is_numeric(trim($output))) {
                return (int) round((float) trim($output));
            }
        } catch (\Exception $e) {
            // Return null if duration extraction fails
        }

        return null;
    }

    public function removeTrack(string $id): void
    {
        $this->tracks = array_values(array_filter($this->tracks, fn ($track) => $track['id'] != $id));
        $this->reorderTracks();
    }

    public function updateTrackOrder(array $orderedIds): void
    {
        $ordered = [];
        foreach ($orderedIds as $index => $id) {
            foreach ($this->tracks as $track) {
                if ($track['id'] == $id) {
                    $track['track_number'] = $index + 1;
                    $ordered[] = $track;
                    break;
                }
            }
        }
        $this->tracks = $ordered;
    }

    public function readCurrentRfidUid(RfidReader $rfidReader, RfidTagNormalizer $normalizer): void
    {
        $rawUid = $rfidReader->readSingleUid();

        if ($rawUid === null) {
            $this->rfidReadFeedback = 'Kein RFID-Chip erkannt.';

            return;
        }

        $normalizedUid = $normalizer->normalize($rawUid);

        if ($normalizedUid === null) {
            $this->rfidReadFeedback = 'RFID-Chip gelesen, aber UID ist ungültig.';

            return;
        }

        $this->rfidUid = $normalizedUid;
        $this->rfidReadFeedback = sprintf('RFID UID %s wurde übernommen.', $normalizedUid);
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'rfidUid' => 'nullable|string|max:255|unique:playlists,rfid_uid,'.$this->playlist->id,
            'volumeProfile' => 'nullable|integer|min:0|max:100',
            'tags' => 'nullable|string|max:500',
            'coverImage' => 'nullable|image|max:5120',
            'tracks' => 'required|array|min:1',
        ]);

        $coverPath = $this->playlist->cover_path;

        if ($this->removeCoverImage && $coverPath) {
            Storage::disk('public')->delete($coverPath);
            $coverPath = null;
        }

        if ($this->coverImage) {
            if ($coverPath) {
                Storage::disk('public')->delete($coverPath);
            }

            $coverPath = $this->coverImage->store('playlist-covers', 'public');
        }

        $this->playlist->update([
            'name' => $this->name,
            'cover_path' => $coverPath,
            'rfid_uid' => app(RfidTagNormalizer::class)->normalize($this->rfidUid),
            'volume_profile' => $this->normalizeVolumeProfile(),
        ]);

        $this->syncPlaylistTags($this->playlist);

        // Get existing track file paths before deletion
        $existingTracks = $this->playlist->tracks->keyBy('id');

        // Delete all existing tracks from database
        foreach ($this->playlist->tracks as $track) {
            $track->delete();
        }

        // Create tracks based on current state
        foreach ($this->tracks as $index => $trackData) {
            if (isset($trackData['file'])) {
                // New uploaded file
                $filePath = $trackData['file']->store('audio', 'public');
                $fullPath = Storage::disk('public')->path($filePath);
            } elseif (isset($trackData['existing']) && $trackData['existing']) {
                // Existing track - reuse the file path
                $existingTrack = $existingTracks->get($trackData['id']);
                $fullPath = $existingTrack->file_path;
            } else {
                // Unknown state, skip
                continue;
            }

            Track::create([
                'playlist_id' => $this->playlist->id,
                'title' => $trackData['title'],
                'file_path' => $fullPath,
                'duration' => $trackData['duration'],
                'track_number' => $index + 1,
            ]);
        }

        // Delete orphaned audio files (files that were in DB but not in $this->tracks)
        $remainingTrackIds = collect($this->tracks)
            ->filter(fn ($track) => isset($track['existing']) && $track['existing'])
            ->pluck('id')
            ->all();

        foreach ($existingTracks as $existingTrack) {
            if (! in_array($existingTrack->id, $remainingTrackIds)) {
                if (file_exists($existingTrack->file_path)) {
                    @unlink($existingTrack->file_path);
                }
            }
        }

        session()->flash('message', "Playlist '{$this->playlist->name}' aktualisiert!");

        return $this->redirect('/', navigate: true);
    }

    public function removeCover(): void
    {
        $this->coverImage = null;
        $this->removeCoverImage = true;
    }

    private function reorderTracks(): void
    {
        foreach ($this->tracks as $index => &$track) {
            $track['track_number'] = $index + 1;
        }
    }

    private function normalizeVolumeProfile(): ?int
    {
        if ($this->volumeProfile === null || $this->volumeProfile === '') {
            return null;
        }

        return (int) $this->volumeProfile;
    }

    private function syncPlaylistTags(Playlist $playlist): void
    {
        $tagNames = collect(explode(',', $this->tags))
            ->map(fn (string $name): string => trim($name))
            ->filter(fn (string $name): bool => $name !== '')
            ->unique(fn (string $name): string => Str::lower($name))
            ->values();

        $tagIds = $tagNames->map(function (string $name): int {
            $slug = Str::slug($name);

            if ($slug === '') {
                $slug = 'tag-'.substr(md5($name), 0, 10);
            }

            $tag = Tag::firstOrCreate(
                ['slug' => $slug],
                ['name' => $name],
            );

            return $tag->id;
        });

        $playlist->tags()->sync($tagIds->all());
    }

    public function render()
    {
        return view('livewire.playlists.edit');
    }
}
