<?php

namespace App\Services;

use App\Models\Playlist;
use Illuminate\Support\Facades\Log;

class RfidPlaylistPlayer
{
    public function __construct(
        private PlayerManager $playerManager,
        private RfidTagNormalizer $normalizer,
    ) {}

    public function playForUid(string $uid): ?Playlist
    {
        $normalizedUid = $this->normalizer->normalize($uid);

        if ($normalizedUid === null) {
            return null;
        }

        $playlist = Playlist::query()->where('rfid_uid', $normalizedUid)->first();

        if ($playlist === null) {
            Log::info('RFID chip scanned without mapped playlist.', ['rfid_uid' => $normalizedUid]);

            return null;
        }

        if ($playlist->volume_profile !== null) {
            $this->playerManager->setVolume($playlist->volume_profile);
        }

        $this->playerManager->playPlaylist(
            playlist: $playlist,
            source: 'rfid',
            trigger: 'present',
            rfidUid: $normalizedUid,
        );

        Log::info('Started playlist from RFID chip.', [
            'rfid_uid' => $normalizedUid,
            'playlist_id' => $playlist->id,
        ]);

        return $playlist;
    }
}
