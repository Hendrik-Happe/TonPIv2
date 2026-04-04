<?php

namespace App\Actions\Player;

use App\Models\Playlist;
use App\Services\PlayerManager;

class PlayPlaylist
{
    public function __construct(
        private PlayerManager $playerManager
    ) {}

    public function execute(Playlist $playlist): void
    {
        $this->playerManager->playPlaylist($playlist, 'ui', 'manual');
    }
}
