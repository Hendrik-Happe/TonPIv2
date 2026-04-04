<?php

namespace App\Livewire;

use App\Services\BackupManager;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use RuntimeException;

#[Title('Backup & Restore')]
class BackupRestore extends Component
{
    public bool $appendMode = false;

    public ?string $statusMessage = null;

    public string $statusType = 'info';

    #[Computed]
    public function backups(): array
    {
        return app(BackupManager::class)->listBackups();
    }

    public function createBackup(): void
    {
        try {
            $archivePath = app(BackupManager::class)->createBackup(true);

            $this->statusType = 'success';
            $this->statusMessage = sprintf('Backup created: %s', basename($archivePath));
        } catch (RuntimeException $exception) {
            $this->statusType = 'error';
            $this->statusMessage = $exception->getMessage();
        }
    }

    public function restoreBackup(string $fileName): void
    {
        try {
            $backupPath = app(BackupManager::class)->resolveBackupPath($fileName);
            $result = app(BackupManager::class)->restoreBackup($backupPath, ! $this->appendMode);

            $this->statusType = 'success';
            $this->statusMessage = sprintf(
                'Restore finished. Playlists: %d, Tracks: %d.',
                $result['playlists'] ?? 0,
                $result['tracks'] ?? 0,
            );
        } catch (RuntimeException $exception) {
            $this->statusType = 'error';
            $this->statusMessage = $exception->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.backup-restore');
    }
}
