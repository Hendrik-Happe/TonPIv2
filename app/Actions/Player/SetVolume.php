<?php

namespace App\Actions\Player;

use App\Services\PlayerManager;

class SetVolume
{
    public function __construct(
        private PlayerManager $playerManager
    ) {}

    public function execute(int $percentage): void
    {
        $this->playerManager->setVolume($percentage);
    }
}
