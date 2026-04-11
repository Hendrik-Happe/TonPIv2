<?php

namespace App\Jobs;

use App\Models\PlayerState;
use App\Models\Track;
use App\Services\PlayerManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;

class PlayTrack implements ShouldQueue
{
    use Queueable;

    private Track $track;

    public $tries = 1;

    public $timeout = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(Track $track)
    {
        $this->track = $track;
    }

    /**
     * Execute the job.
     */
    public function handle(PlayerManager $playerManager): void
    {
        try {
            $state = PlayerState::global()->fresh();

            // Ignore outdated jobs when users skip quickly and multiple jobs are queued.
            if ((int) $state->current_track_id !== (int) $this->track->id || $state->status !== 'playing') {
                \Log::info('PlayTrack Job: Skipped stale playback job', [
                    'job_track_id' => $this->track->id,
                    'state_track_id' => $state->current_track_id,
                    'state_status' => $state->status,
                ]);

                return;
            }

            $fifoPath = config('player.player.fifo_path');
            $filePath = $this->track->file_path;
            $isRemoteStream = filter_var($filePath, FILTER_VALIDATE_URL) !== false;

            \Log::info('PlayTrack Job: Starting playback', [
                'track_id' => $this->track->id,
                'track_title' => $this->track->title,
                'file_path' => $filePath,
                'fifo_path' => $fifoPath,
            ]);

            // Only local files must exist on disk. Remote streams are passed directly to mplayer.
            if (! $isRemoteStream && ! file_exists($filePath)) {
                \Log::error('PlayTrack Job: File not found', [
                    'track_id' => $this->track->id,
                    'file_path' => $filePath,
                ]);
                throw new \Exception("Audio file not found: {$filePath}");
            }

            // Erstelle FIFO falls nicht vorhanden
            if (! file_exists($fifoPath)) {
                if (! posix_mkfifo($fifoPath, 0666)) {
                    \Log::error('PlayTrack Job: Failed to create FIFO', [
                        'fifo_path' => $fifoPath,
                    ]);
                    throw new \Exception("Failed to create FIFO at: {$fifoPath}");
                }
            }

            // Starte mplayer
            $command = $this->buildMplayerCommand($fifoPath, $filePath);

            \Log::info('PlayTrack Job: Executing command', [
                'command' => $command,
            ]);

            $pid = (int) trim(shell_exec($command));

            if ($pid > 0) {
                // Register PID with PlayerManager
                $playerManager->registerMplayerPid($pid);

                \Log::info('PlayTrack Job: mplayer started successfully', [
                    'track_id' => $this->track->id,
                    'track_title' => $this->track->title,
                    'pid' => $pid,
                ]);

                // Start a background process to monitor when mplayer finishes
                $this->monitorMplayerProcess($pid);
            } else {
                \Log::error('PlayTrack Job: Failed to start mplayer', [
                    'track_id' => $this->track->id,
                    'track_title' => $this->track->title,
                ]);
                throw new \Exception('Failed to start mplayer process');
            }
        } catch (\Exception $e) {
            \Log::error('PlayTrack Job: Error', [
                'track_id' => $this->track->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    private function buildMplayerCommand(string $fifoPath, string $filePath): string
    {
        $logSuffix = time();
        $escapedFifo = escapeshellarg($fifoPath);
        $escapedSource = escapeshellarg($filePath);

        if ($this->isPlaylistStream($filePath)) {
            return sprintf(
                'nohup mplayer -slave -quiet -input file=%s -playlist %s > /tmp/mplayer_%s.log 2>&1 & echo $!',
                $escapedFifo,
                $escapedSource,
                $logSuffix,
            );
        }

        return sprintf(
            'nohup mplayer -slave -quiet -input file=%s %s > /tmp/mplayer_%s.log 2>&1 & echo $!',
            $escapedFifo,
            $escapedSource,
            $logSuffix,
        );
    }

    private function isPlaylistStream(string $filePath): bool
    {
        if (filter_var($filePath, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $path = (string) parse_url($filePath, PHP_URL_PATH);

        return Str::endsWith(Str::lower($path), ['.m3u', '.m3u8']);
    }

    /**
     * Monitor the mplayer process and notify when it finishes.
     */
    private function monitorMplayerProcess(int $pid): void
    {
        // Create a simple bash script to monitor the process
        $phpPath = PHP_BINARY;
        $artisanPath = base_path('artisan');

        $monitorScript = sprintf(
            '(while kill -0 %d 2>/dev/null; do sleep 1; done; %s %s player:track-finished --pid=%d) > /dev/null 2>&1 &',
            $pid,
            escapeshellarg($phpPath),
            escapeshellarg($artisanPath),
            $pid
        );

        shell_exec($monitorScript);

        \Log::info('PlayTrack Job: Monitor process started', ['pid' => $pid]);
    }
}
