<?php

namespace App\Services;

use App\Jobs\PlayTrack;
use App\Models\PlayerState;
use App\Models\Playlist;
use App\Models\Track;

class PlayerManager
{
    private PlayerState $state;

    private string $fifoPath;

    public function __construct(private PlaybackEventLogger $eventLogger)
    {
        $this->state = PlayerState::global();
        $this->fifoPath = config('player.player.fifo_path');
        $this->ensureFifoExists();
    }

    /**
     * Start playing a new playlist from the beginning.
     */
    public function playPlaylist(
        Playlist $playlist,
        string $source = 'system',
        ?string $trigger = null,
        ?string $rfidUid = null,
    ): void {
        $this->stop($source, $trigger, false);

        $tracks = $playlist->tracks()->orderBy('track_number')->get();

        if ($tracks->isEmpty()) {
            return;
        }

        $this->state->update([
            'current_playlist_id' => $playlist->id,
            'current_track_id' => null,
            'current_position' => 0,
            'status' => 'stopped',
        ]);

        $this->playTrackAtPosition(0);

        $this->eventLogger->log(
            action: 'started',
            source: $source,
            playlistId: $playlist->id,
            rfidUid: $rfidUid,
            trigger: $trigger,
        );
    }

    /**
     * Play track at specific position in current playlist.
     */
    public function playTrackAtPosition(int $position): void
    {
        $playlist = $this->state->currentPlaylist;

        if (! $playlist) {
            return;
        }

        $tracks = $playlist->tracks()->orderBy('track_number')->get();

        if (! isset($tracks[$position])) {
            // End of playlist reached
            $this->handlePlaylistEnd();

            return;
        }

        $track = $tracks[$position];

        $this->killCurrentMplayerProcess();

        $this->state->update([
            'current_track_id' => $track->id,
            'current_position' => $position,
            'status' => 'playing',
        ]);

        // Dispatch job to play the track
        PlayTrack::dispatch($track);
    }

    /**
     * Pause the current track.
     */
    public function pause(
        string $source = 'system',
        ?string $trigger = null,
        ?string $rfidUid = null,
    ): void {
        if (! $this->state->isPlaying()) {
            return;
        }

        if ($this->isMplayerProcessRunning()) {
            $this->sendCommandToFifo('pause');
        }

        $this->state->update(['status' => 'paused']);

        $this->eventLogger->log(
            action: 'paused',
            source: $source,
            playlistId: $this->state->current_playlist_id,
            trackId: $this->state->current_track_id,
            rfidUid: $rfidUid,
            trigger: $trigger,
        );
    }

    /**
     * Resume the current track.
     */
    public function resume(
        string $source = 'system',
        ?string $trigger = null,
        ?string $rfidUid = null,
    ): void {
        if ($this->state->isPaused()) {
            if ($this->isMplayerProcessRunning()) {
                $this->sendCommandToFifo('pause');
                $this->state->update(['status' => 'playing']);

                $this->eventLogger->log(
                    action: 'resumed',
                    source: $source,
                    playlistId: $this->state->current_playlist_id,
                    trackId: $this->state->current_track_id,
                    rfidUid: $rfidUid,
                    trigger: $trigger,
                );
            } else {
                // Process died, restart current track
                $this->playTrackAtPosition($this->state->current_position);

                $this->eventLogger->log(
                    action: 'resumed',
                    source: $source,
                    playlistId: $this->state->current_playlist_id,
                    trackId: $this->state->current_track_id,
                    rfidUid: $rfidUid,
                    trigger: $trigger,
                    context: ['mode' => 'restart-current-track'],
                );
            }
        }
    }

    /**
     * Toggle between play and pause.
     */
    public function togglePlayPause(
        string $source = 'system',
        ?string $trigger = null,
        ?string $rfidUid = null,
    ): void {
        if ($this->state->isPlaying()) {
            $this->pause($source, $trigger, $rfidUid);
        } elseif ($this->state->isPaused()) {
            $this->resume($source, $trigger, $rfidUid);
        }
    }

    /**
     * Skip to the next track.
     */
    public function next(
        string $source = 'system',
        ?string $trigger = null,
        ?string $rfidUid = null,
    ): void {
        if (! $this->state->current_playlist_id) {
            return;
        }

        $this->playTrackAtPosition($this->state->current_position + 1);

        $this->eventLogger->log(
            action: 'next',
            source: $source,
            playlistId: $this->state->current_playlist_id,
            trackId: $this->state->current_track_id,
            rfidUid: $rfidUid,
            trigger: $trigger,
        );
    }

    /**
     * Go to the previous track.
     */
    public function previous(
        string $source = 'system',
        ?string $trigger = null,
        ?string $rfidUid = null,
    ): void {
        if (! $this->state->current_playlist_id) {
            return;
        }

        $newPosition = max(0, $this->state->current_position - 1);
        $this->playTrackAtPosition($newPosition);

        $this->eventLogger->log(
            action: 'previous',
            source: $source,
            playlistId: $this->state->current_playlist_id,
            trackId: $this->state->current_track_id,
            rfidUid: $rfidUid,
            trigger: $trigger,
        );
    }

    /**
     * Stop playback completely.
     */
    public function stop(
        string $source = 'system',
        ?string $trigger = null,
        bool $shouldLog = true,
        ?string $rfidUid = null,
    ): void {
        $playlistId = $this->state->current_playlist_id;
        $trackId = $this->state->current_track_id;

        $this->killCurrentMplayerProcess();

        $this->state->update([
            'status' => 'stopped',
            'mplayer_pid' => null,
            'expected_pid' => null,
        ]);

        if ($shouldLog) {
            $this->eventLogger->log(
                action: 'stopped',
                source: $source,
                playlistId: $playlistId,
                trackId: $trackId,
                rfidUid: $rfidUid,
                trigger: $trigger,
            );
        }
    }

    /**
     * Set repeat mode.
     */
    public function setRepeatMode(string $mode): void
    {
        if (in_array($mode, ['none', 'one', 'all'])) {
            $this->state->update(['repeat_mode' => $mode]);
        }
    }

    /**
     * Set playback volume via system mixer.
     */
    public function setVolume(int $percentage): void
    {
        $normalizedPercentage = max(0, min(100, $percentage));
        $systemVolume = $this->mapPlayerToSystemVolume($normalizedPercentage);

        $this->state->update([
            'volume_percentage' => $normalizedPercentage,
        ]);

        shell_exec(sprintf("amixer -M set 'PCM' %d%% 2>/dev/null", $systemVolume));
    }

    /**
     * Handle what happens when a track finishes playing.
     */
    public function onTrackFinished(int $pid = 0): void
    {
        $freshState = $this->state->fresh();

        // Ignore if this is not the expected process (e.g. process was killed intentionally)
        if ($pid > 0 && $freshState->expected_pid !== $pid) {
            \Log::info('Ignoring track-finished from stale PID', [
                'received_pid' => $pid,
                'expected_pid' => $freshState->expected_pid,
            ]);

            return;
        }

        \Log::info('Track finished handler called', [
            'current_position' => $freshState->current_position,
            'repeat_mode' => $freshState->repeat_mode,
        ]);

        // Refresh state for latest values
        $this->state = $freshState;

        $repeatMode = $this->state->repeat_mode;

        if ($repeatMode === 'one') {
            \Log::info('Repeating current track');
            $this->playTrackAtPosition($this->state->current_position);
        } elseif ($repeatMode === 'all' || $repeatMode === 'none') {
            \Log::info('Moving to next track');
            $this->next();
        }
    }

    /**
     * Get current player state.
     */
    public function getState(): PlayerState
    {
        return $this->state->fresh();
    }

    /**
     * Register mplayer process ID.
     */
    public function registerMplayerPid(int $pid): void
    {
        $this->state->update([
            'mplayer_pid' => $pid,
            'expected_pid' => $pid,
        ]);
    }

    /**
     * Handle end of playlist based on repeat mode.
     */
    private function handlePlaylistEnd(): void
    {
        if ($this->state->repeat_mode === 'all') {
            // Start from beginning
            $this->playTrackAtPosition(0);
        } else {
            // Stop playback
            $this->state->update(['status' => 'stopped']);
        }
    }

    /**
     * Send command to mplayer via FIFO.
     */
    private function sendCommandToFifo(string $command): void
    {
        if (file_exists($this->fifoPath)) {
            file_put_contents($this->fifoPath, $command."\n");
        }
    }

    /**
     * Kill current mplayer process if running.
     */
    private function killCurrentMplayerProcess(): void
    {
        $pid = $this->state->mplayer_pid;

        // Immediately clear expected_pid so any pending monitor callbacks are ignored
        $this->state->update([
            'mplayer_pid' => null,
            'expected_pid' => null,
        ]);

        if ($pid) {
            // Graceful SIGTERM to the specific PID – does not block, works cross-user via /proc check
            shell_exec("kill -TERM {$pid} 2>/dev/null");
            usleep(150000);
        }

        // Kill ALL mplayer processes as a safety net (only affects processes we own)
        shell_exec('pkill -9 mplayer 2>/dev/null');
        usleep(100000);
    }

    /**
     * Check if the current mplayer process is still running.
     * Uses /proc filesystem instead of posix_kill so it works across different users.
     */
    private function isMplayerProcessRunning(): bool
    {
        if (! $this->state->mplayer_pid) {
            return false;
        }

        return file_exists('/proc/'.$this->state->mplayer_pid);
    }

    /**
     * Ensure FIFO exists.
     */
    private function ensureFifoExists(): void
    {
        if (! file_exists($this->fifoPath)) {
            posix_mkfifo($this->fifoPath, 0666);
        }
    }

    /**
     * Map app volume (0-100) to configured system range.
     */
    private function mapPlayerToSystemVolume(int $percentage): int
    {
        $configuredMin = (int) config('player.player.min_volume', 20);
        $configuredMax = (int) config('player.player.max_volume', 100);

        $minVolume = min($configuredMin, $configuredMax);
        $maxVolume = max($configuredMin, $configuredMax);

        return (int) round($minVolume + (($maxVolume - $minVolume) * ($percentage / 100)));
    }
}
