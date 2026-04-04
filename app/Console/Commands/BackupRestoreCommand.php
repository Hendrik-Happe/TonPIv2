<?php

namespace App\Console\Commands;

use App\Services\BackupManager;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use RuntimeException;

#[Signature('backup:restore {file? : Backup zip path (defaults to latest backup)} {--append : Keep existing playlists and append restored ones}')]
#[Description('Restore playlists, tracks, RFID mappings and player settings from a backup zip')]
class BackupRestoreCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(BackupManager $backupManager): int
    {
        $fileArgument = $this->argument('file');

        $backupPath = is_string($fileArgument) && $fileArgument !== ''
            ? $fileArgument
            : $backupManager->latestBackupPath();

        if ($backupPath === null) {
            $this->error('No backup file found to restore.');

            return self::FAILURE;
        }

        if (! str_contains($backupPath, DIRECTORY_SEPARATOR)) {
            $backupPath = $backupManager->resolveBackupPath($backupPath);
        }

        $replaceExisting = ! (bool) $this->option('append');

        try {
            $result = $backupManager->restoreBackup($backupPath, $replaceExisting);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Restore finished. Playlists: %d, Tracks: %d',
            $result['playlists'] ?? 0,
            $result['tracks'] ?? 0,
        ));

        return self::SUCCESS;
    }
}
