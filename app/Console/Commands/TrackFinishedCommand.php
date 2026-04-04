<?php

namespace App\Console\Commands;

use App\Services\PlayerManager;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('player:track-finished {--pid= : The mplayer PID that finished}')]
#[Description('Handle track finished event from mplayer monitor')]
class TrackFinishedCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(PlayerManager $playerManager): void
    {
        $pid = (int) $this->option('pid');
        \Log::info('Track finished command executed', ['pid' => $pid]);
        $playerManager->onTrackFinished($pid);
    }
}
