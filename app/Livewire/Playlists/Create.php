<?php

namespace App\Livewire\Playlists;

use App\Models\Playlist;
use App\Models\Track;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

class Create extends Component
{
    use WithFileUploads;

    #[Validate('required|string|max:255')]
    public string $name = '';

    public array $tracks = [];

    public array $uploadedFiles = [];

    public function mount(): void
    {
        // Initialize with empty state
    }

    public function updatedUploadedFiles(): void
    {
        $this->validate([
            'uploadedFiles.*' => 'mimes:mp3,wav,ogg,flac,m4a,aac,wma|max:102400',
        ]);

        foreach ($this->uploadedFiles as $file) {
            // Extract original filename without extension as title
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

            // Store file temporarily to read metadata
            $tempPath = $file->getRealPath();

            // Extract duration using ffprobe
            $duration = $this->extractDuration($tempPath);

            $this->tracks[] = [
                'id' => uniqid(),
                'title' => $originalName,
                'file' => $file,
                'file_name' => $file->getClientOriginalName(),
                'duration' => $duration,
                'track_number' => count($this->tracks) + 1,
            ];
        }

        // Reset uploaded files after processing
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
        $this->tracks = array_values(array_filter($this->tracks, fn ($track) => $track['id'] !== $id));
        $this->reorderTracks();
    }

    public function updateTrackOrder(array $orderedIds): void
    {
        $ordered = [];
        foreach ($orderedIds as $index => $id) {
            foreach ($this->tracks as $track) {
                if ($track['id'] === $id) {
                    // Update track_number immediately during reordering
                    $track['track_number'] = $index + 1;
                    $ordered[] = $track;
                    break;
                }
            }
        }
        $this->tracks = $ordered;
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'tracks' => 'required|array|min:1',
        ]);

        $playlist = Playlist::create([
            'name' => $this->name,
        ]);

        // Ensure tracks are in the correct order before saving
        foreach ($this->tracks as $index => $trackData) {
            // Store the audio file permanently
            $filePath = $trackData['file']->store('audio', 'public');

            Track::create([
                'playlist_id' => $playlist->id,
                'title' => $trackData['title'],
                'file_path' => Storage::disk('public')->path($filePath),
                'duration' => $trackData['duration'],
                'track_number' => $index + 1, // Use array index to ensure correct order
            ]);
        }

        session()->flash('message', "Playlist '{$playlist->name}' created successfully!");

        return $this->redirect('/playlists', navigate: true);
    }

    private function reorderTracks(): void
    {
        foreach ($this->tracks as $index => &$track) {
            $track['track_number'] = $index + 1;
        }
    }

    public function render()
    {
        return view('livewire.playlists.create');
    }
}
