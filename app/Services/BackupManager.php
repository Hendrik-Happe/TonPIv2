<?php

namespace App\Services;

use App\Models\PlayerState;
use App\Models\Playlist;
use App\Models\Track;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class BackupManager
{
    public function createBackup(bool $includeAudio = true): string
    {
        $backupDirectory = $this->backupDirectory();
        File::ensureDirectoryExists($backupDirectory);

        $fileName = sprintf('tonpi-backup-%s.zip', now()->format('Ymd-His'));
        $archivePath = $backupDirectory.DIRECTORY_SEPARATOR.$fileName;

        $zip = new ZipArchive;

        if ($zip->open($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Backup archive could not be created.');
        }

        $playlists = Playlist::query()
            ->with(['tracks' => fn ($query) => $query->orderBy('track_number')])
            ->orderBy('name')
            ->get();

        $manifest = [
            'version' => 1,
            'created_at' => now()->toIso8601String(),
            'settings' => $this->serializeSettings(),
            'playlists' => [],
        ];

        foreach ($playlists as $playlist) {
            $playlistPayload = [
                'name' => $playlist->name,
                'rfid_uid' => $playlist->rfid_uid,
                'volume_profile' => $playlist->volume_profile,
                'tracks' => [],
            ];

            foreach ($playlist->tracks as $track) {
                $relativePath = $this->toPublicRelativePath($track->file_path);

                $playlistPayload['tracks'][] = [
                    'title' => $track->title,
                    'duration' => $track->duration,
                    'track_number' => $track->track_number,
                    'file_path' => $relativePath,
                ];

                if ($includeAudio && $relativePath !== null) {
                    $sourcePath = Storage::disk('public')->path($relativePath);
                    $zipPath = 'files/'.$relativePath;

                    if (is_file($sourcePath)) {
                        $zip->addFile($sourcePath, $zipPath);
                    }
                }
            }

            $manifest['playlists'][] = $playlistPayload;
        }

        $manifestJson = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($manifestJson === false) {
            $zip->close();

            throw new RuntimeException('Backup manifest could not be encoded.');
        }

        $zip->addFromString('manifest.json', $manifestJson);
        $zip->close();

        return $archivePath;
    }

    public function restoreBackup(string $archivePath, bool $replaceExisting = true): array
    {
        if (! is_file($archivePath)) {
            throw new RuntimeException('Backup file does not exist.');
        }

        $extractDirectory = storage_path('app/private/backups/tmp/'.Str::uuid());
        File::ensureDirectoryExists($extractDirectory);

        $zip = new ZipArchive;

        if ($zip->open($archivePath) !== true) {
            throw new RuntimeException('Backup archive could not be opened.');
        }

        $zip->extractTo($extractDirectory);
        $zip->close();

        $manifestPath = $extractDirectory.DIRECTORY_SEPARATOR.'manifest.json';

        if (! is_file($manifestPath)) {
            File::deleteDirectory($extractDirectory);

            throw new RuntimeException('Backup manifest is missing.');
        }

        $manifest = json_decode((string) file_get_contents($manifestPath), true);

        if (! is_array($manifest) || ! isset($manifest['playlists']) || ! is_array($manifest['playlists'])) {
            File::deleteDirectory($extractDirectory);

            throw new RuntimeException('Backup manifest is invalid.');
        }

        $restoredPlaylists = 0;
        $restoredTracks = 0;

        DB::transaction(function () use (&$restoredPlaylists, &$restoredTracks, $manifest, $extractDirectory, $replaceExisting): void {
            if ($replaceExisting) {
                Playlist::query()->delete();
            }

            foreach ($manifest['playlists'] as $playlistData) {
                if (! is_array($playlistData) || ! isset($playlistData['name'])) {
                    continue;
                }

                $playlist = Playlist::query()->create([
                    'name' => (string) $playlistData['name'],
                    'rfid_uid' => isset($playlistData['rfid_uid']) ? (string) $playlistData['rfid_uid'] : null,
                    'volume_profile' => isset($playlistData['volume_profile']) && $playlistData['volume_profile'] !== ''
                        ? (int) $playlistData['volume_profile']
                        : null,
                ]);

                $restoredPlaylists++;

                $tracks = $playlistData['tracks'] ?? [];

                foreach ($tracks as $trackData) {
                    if (! is_array($trackData) || ! isset($trackData['title'], $trackData['track_number'])) {
                        continue;
                    }

                    $relativePath = isset($trackData['file_path']) ? (string) $trackData['file_path'] : null;

                    if ($relativePath === null || $relativePath === '') {
                        continue;
                    }

                    $sourcePath = $extractDirectory.DIRECTORY_SEPARATOR.'files'.DIRECTORY_SEPARATOR.$relativePath;

                    if (! is_file($sourcePath)) {
                        throw new RuntimeException(sprintf('Audio file missing in backup: %s', $relativePath));
                    }

                    $destinationPath = Storage::disk('public')->path($relativePath);
                    File::ensureDirectoryExists(dirname($destinationPath));
                    File::copy($sourcePath, $destinationPath);

                    Track::query()->create([
                        'playlist_id' => $playlist->id,
                        'title' => (string) $trackData['title'],
                        'file_path' => $destinationPath,
                        'duration' => isset($trackData['duration']) && $trackData['duration'] !== null
                            ? (int) $trackData['duration']
                            : null,
                        'track_number' => (int) $trackData['track_number'],
                    ]);

                    $restoredTracks++;
                }
            }

            $this->restoreSettings($manifest['settings'] ?? []);
        });

        File::deleteDirectory($extractDirectory);

        return [
            'playlists' => $restoredPlaylists,
            'tracks' => $restoredTracks,
        ];
    }

    public function listBackups(): array
    {
        $backupDirectory = $this->backupDirectory();

        if (! is_dir($backupDirectory)) {
            return [];
        }

        $files = collect(File::files($backupDirectory))
            ->filter(fn ($file) => Str::endsWith($file->getFilename(), '.zip'))
            ->sortByDesc(fn ($file) => $file->getMTime())
            ->map(fn ($file) => [
                'name' => $file->getFilename(),
                'size' => $file->getSize(),
                'updated_at' => date('Y-m-d H:i:s', $file->getMTime()),
            ])
            ->values()
            ->all();

        return $files;
    }

    public function resolveBackupPath(string $fileName): string
    {
        $safeFileName = basename($fileName);

        if ($safeFileName === '') {
            throw new RuntimeException('Backup file name is invalid.');
        }

        return $this->backupDirectory().DIRECTORY_SEPARATOR.$safeFileName;
    }

    public function latestBackupPath(): ?string
    {
        $backups = $this->listBackups();

        if ($backups === []) {
            return null;
        }

        return $this->resolveBackupPath($backups[0]['name']);
    }

    private function backupDirectory(): string
    {
        return storage_path('app/private/backups');
    }

    private function toPublicRelativePath(?string $absolutePath): ?string
    {
        if ($absolutePath === null || $absolutePath === '') {
            return null;
        }

        $publicRoot = rtrim(Storage::disk('public')->path(''), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        if (! str_starts_with($absolutePath, $publicRoot)) {
            return null;
        }

        return ltrim(substr($absolutePath, strlen($publicRoot)), DIRECTORY_SEPARATOR);
    }

    private function serializeSettings(): array
    {
        $state = PlayerState::global();

        return [
            'repeat_mode' => $state->repeat_mode,
            'volume_percentage' => $state->volume_percentage,
        ];
    }

    private function restoreSettings(array $settings): void
    {
        $state = PlayerState::global();

        $volume = isset($settings['volume_percentage']) ? (int) $settings['volume_percentage'] : $state->volume_percentage;

        $state->update([
            'repeat_mode' => isset($settings['repeat_mode']) ? (string) $settings['repeat_mode'] : $state->repeat_mode,
            'volume_percentage' => max(0, min(100, $volume)),
            'status' => 'stopped',
            'current_playlist_id' => null,
            'current_track_id' => null,
            'current_position' => 0,
        ]);
    }
}
