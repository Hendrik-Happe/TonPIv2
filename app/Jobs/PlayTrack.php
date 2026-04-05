<?php

namespace App\Jobs;

use App\Models\Track;
use App\Services\PlayerManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

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
            $fifoPath = config('player.player.fifo_path');
            $filePath = $this->track->file_path;

            \Log::info("PlayTrack Job: Starting playback", [
                'track_id' => $this->track->id,
                'track_title' => $this->track->title,
                'file_path' => $filePath,
                'fifo_path' => $fifoPath,
            ]);

            // Überprüfe ob Datei existiert
            if (! file_exists($filePath)) {
                \Log::error("PlayTrack Job: File not found", [
                    'track_id' => $this->track->id,
                    'file_path' => $filePath,
                ]);
                throw new \Exception("Audio file not found: {$filePath}");
            }

            // Erstelle FIFO falls nicht vorhanden
            if (! file_exists($fifoPath)) {
                if (! posix_mkfifo($fifoPath, 0666)) {
                    \Log::error("PlayTrack Job: Failed to create FIFO", [
                        'fifo_path' => $fifoPath,
                    ]);
                    throw new \Exception("Failed to create FIFO at: {$fifoPath}");
                }
            }

            // Starte mplayer
            $command = sprintf(
                'nohup mplayer -slave -quiet -input file=%s %s > /tmp/mplayer_%s.log 2>&1 & echo $!',
                escapeshellarg($fifoPath),
                escapeshellarg($filePath),
                time()
            );

            \Log::info("PlayTrack Job: Executing command", [
                'command' => $command,
            ]);

            $pid = (int) trim(shell_exec($command));

            if ($pid > 0) {
                // Register PID with PlayerManager
                $playerManager->registerMplayerPid($pid);

                \Log::info("PlayTrack Job: mplayer started successfully", [
                    'track_id' => $this->track->id,
                    'track_title' => $this->track->title,
                    'pid' => $pid,
                ]);

                // Start a background process to monitor when mplayer finishes
                $this->monitorMplayerProcess($pid);
            } else {
                \Log::error("PlayTrack Job: Failed to start mplayer", [
                    'track_id' => $this->track->id,
                    'track_title' => $this->track->title,
                ]);
                throw new \Exception("Failed to start mplayer process");
            }
        } catch (\Exception $e) {
            \Log::error("PlayTrack Job: Error", [
                'track_id' => $this->track->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
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

        \Log::info("PlayTrack Job: Monitor process started", ['pid' => $pid]);
    }
}
