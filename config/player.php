<?php

return [
    'player' => [
        'fifo_path' => env('PLAYER_FIFO_PATH', '/tmp/tonpi_player_fifo'),
        'min_volume' => (int) env('PLAYER_MIN_VOLUME', 20),
        'max_volume' => (int) env('PLAYER_MAX_VOLUME', 100),
    ],
];
