<?php

namespace Tests\Feature;

use App\Services\BackupManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackupCommandsTest extends TestCase
{
    use RefreshDatabase;

    public function test_backup_create_command_reports_created_archive(): void
    {
        $this->mock(BackupManager::class)
            ->shouldReceive('createBackup')
            ->once()
            ->with(true)
            ->andReturn('/tmp/tonpi-backup.zip');

        $this->artisan('backup:create')
            ->expectsOutput('Backup created: /tmp/tonpi-backup.zip')
            ->assertSuccessful();
    }

    public function test_backup_restore_command_uses_latest_backup_when_no_file_argument(): void
    {
        $backupManager = $this->mock(BackupManager::class);

        $backupManager
            ->shouldReceive('latestBackupPath')
            ->once()
            ->andReturn('/tmp/tonpi-backup.zip');

        $backupManager
            ->shouldReceive('restoreBackup')
            ->once()
            ->with('/tmp/tonpi-backup.zip', true)
            ->andReturn([
                'playlists' => 2,
                'tracks' => 5,
            ]);

        $this->artisan('backup:restore')
            ->expectsOutput('Restore finished. Playlists: 2, Tracks: 5')
            ->assertSuccessful();
    }
}
