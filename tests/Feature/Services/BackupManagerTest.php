<?php

namespace Tests\Feature\Services;

use App\Models\PlayerState;
use App\Models\Playlist;
use App\Models\Track;
use App\Services\BackupManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BackupManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        File::deleteDirectory(storage_path('app/private/backups'));
        File::deleteDirectory(storage_path('app/public/audio'));
    }

    public function test_it_can_backup_and_restore_playlists_tracks_and_settings(): void
    {
        $relativeAudioPath = 'audio/test-track.mp3';
        $absoluteAudioPath = Storage::disk('public')->path($relativeAudioPath);

        File::ensureDirectoryExists(dirname($absoluteAudioPath));
        file_put_contents($absoluteAudioPath, 'fake-audio-data');

        $playlist = Playlist::query()->create([
            'name' => 'Backup Playlist',
            'rfid_uid' => 'A1B2C3D4',
            'volume_profile' => 47,
        ]);

        Track::query()->create([
            'playlist_id' => $playlist->id,
            'title' => 'Backup Track',
            'file_path' => $absoluteAudioPath,
            'duration' => 123,
            'track_number' => 1,
        ]);

        PlayerState::global()->update([
            'repeat_mode' => 'one',
            'volume_percentage' => 33,
        ]);

        $backupManager = app(BackupManager::class);
        $archivePath = $backupManager->createBackup(true);

        $this->assertFileExists($archivePath);

        Playlist::query()->delete();
        PlayerState::global()->update([
            'repeat_mode' => 'all',
            'volume_percentage' => 99,
        ]);

        File::delete($absoluteAudioPath);

        $result = $backupManager->restoreBackup($archivePath, true);

        $this->assertSame(1, $result['playlists']);
        $this->assertSame(1, $result['tracks']);

        $this->assertDatabaseHas('playlists', [
            'name' => 'Backup Playlist',
            'rfid_uid' => 'A1B2C3D4',
            'volume_profile' => 47,
        ]);

        $restoredTrack = Track::query()->first();

        $this->assertNotNull($restoredTrack);
        $this->assertSame('Backup Track', $restoredTrack->title);
        $this->assertFileExists($restoredTrack->file_path);

        $playerState = PlayerState::global()->fresh();
        $this->assertSame('one', $playerState->repeat_mode);
        $this->assertSame(33, $playerState->volume_percentage);
    }
}
