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
        $fifoPath = config('player.player.fifo_path');

        if (! file_exists($fifoPath)) {
            posix_mkfifo($fifoPath, 0666);
        }

        \Log::info("Playing track: {$this->track->title} from file: {$this->track->file_path}");

        // Start mplayer as a detached background process
        $command = sprintf(
            'nohup mplayer -slave -quiet -input file=%s %s > /dev/null 2>&1 & echo $!',
            escapeshellarg($fifoPath),
            escapeshellarg($this->track->file_path)
        );

        $pid = (int) trim(shell_exec($command));

        if ($pid > 0) {
            // Register PID with PlayerManager
            $playerManager->registerMplayerPid($pid);

            \Log::info("mplayer started with PID: {$pid}");

            // Start a background process to monitor when mplayer finishes
            $this->monitorMplayerProcess($pid);
        } else {
            \Log::error("Failed to start mplayer process for track: {$this->track->title}");
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

        \Log::info("Monitor process started for PID: {$pid}");
    }
}
