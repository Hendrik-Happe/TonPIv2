<?php

namespace App\Console\Commands;

use App\Services\RfidPlaylistPlayer;
use App\Services\RfidReader;
use App\Services\RfidTagNormalizer;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use RuntimeException;

#[Signature('rfid:listen')]
#[Description('Listen for RFID scans and start mapped playlists')]
class ListenForRfidScans extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(
        RfidReader $reader,
        RfidPlaylistPlayer $playlistPlayer,
        RfidTagNormalizer $normalizer,
    ): int {
        $debounceSeconds = max(0, (int) config('rfid.debounce_seconds', 2));
        $lastUid = null;
        $lastSeenAt = null;

        $this->info('Listening for RFID chip scans...');

        try {
            $reader->listen(function (string $rawUid) use ($playlistPlayer, $normalizer, $debounceSeconds, &$lastUid, &$lastSeenAt): void {
                $normalizedUid = $normalizer->normalize($rawUid);

                if ($normalizedUid === null) {
                    return;
                }

                $now = now();

                if (
                    $lastUid === $normalizedUid
                    && $lastSeenAt !== null
                    && $now->diffInSeconds($lastSeenAt) < $debounceSeconds
                ) {
                    return;
                }

                $lastUid = $normalizedUid;
                $lastSeenAt = $now;

                $playlist = $playlistPlayer->playForUid($normalizedUid);

                if ($playlist === null) {
                    $this->warn(sprintf('No playlist linked to RFID chip %s.', $normalizedUid));

                    return;
                }

                $this->info(sprintf('Started playlist "%s" for RFID chip %s.', $playlist->name, $normalizedUid));
            });
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
