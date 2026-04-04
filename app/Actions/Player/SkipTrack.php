<?php

namespace App\Actions\Player;

use App\Services\PlayerManager;

class SkipTrack
{
    public function __construct(
        private PlayerManager $playerManager
    ) {}

    public function execute(): void
    {
        $this->playerManager->next('ui', 'manual');
    }
}
