<?php

namespace App\Services;

use App\Models\PlaybackEvent;
use Illuminate\Support\Facades\Auth;
use Throwable;

class PlaybackEventLogger
{
    public function log(
        string $action,
        string $source,
        ?int $playlistId = null,
        ?int $trackId = null,
        ?string $rfidUid = null,
        ?string $trigger = null,
        array $context = [],
    ): void {
        try {
            PlaybackEvent::query()->create([
                'action' => $action,
                'source' => $source,
                'playlist_id' => $playlistId,
                'track_id' => $trackId,
                'rfid_uid' => $rfidUid,
                'trigger' => $trigger,
                'initiated_by' => Auth::user()?->name,
                'context' => $context,
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
