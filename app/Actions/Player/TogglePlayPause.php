<?php

namespace App\Actions\Player;

use App\Services\PlayerManager;

class TogglePlayPause
{
    public function __construct(
        private PlayerManager $playerManager
    ) {}

    public function execute(): void
    {
        $this->playerManager->togglePlayPause();
    }
}
