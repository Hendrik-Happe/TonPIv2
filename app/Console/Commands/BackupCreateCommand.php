<?php

namespace App\Console\Commands;

use App\Services\BackupManager;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use RuntimeException;

#[Signature('backup:create {--without-audio : Create backup without audio files}')]
#[Description('Create a backup of playlists, tracks, RFID mappings and player settings')]
class BackupCreateCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(BackupManager $backupManager): int
    {
        $includeAudio = ! (bool) $this->option('without-audio');

        try {
            $archivePath = $backupManager->createBackup($includeAudio);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf('Backup created: %s', $archivePath));

        return self::SUCCESS;
    }
}
