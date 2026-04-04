<?php

namespace App\Actions\Player;

use App\Services\PlayerManager;

class SetRepeatMode
{
    public function __construct(
        private PlayerManager $playerManager
    ) {}

    public function execute(string $mode): void
    {
        $this->playerManager->setRepeatMode($mode);
    }
}
