<?php

return [
    'player' => [
        'fifo_path' => env('PLAYER_FIFO_PATH', '/tmp/tonpi_player_fifo'),
        'min_volume' => (int) env('PLAYER_MIN_VOLUME', 20),
        'max_volume' => (int) env('PLAYER_MAX_VOLUME', 100),
    ],

    'gpio' => [
        'enabled' => env('GPIO_ENABLED', true),
        'btn_previous_pin' => (int) env('GPIO_BTN_PREVIOUS_PIN', 17),
        'btn_next_pin' => (int) env('GPIO_BTN_NEXT_PIN', 27),
        'btn_vol_down_pin' => (int) env('GPIO_BTN_VOL_DOWN_PIN', 22),
        'btn_vol_up_pin' => (int) env('GPIO_BTN_VOL_UP_PIN', 23),
        'led_ready_pin' => (int) env('GPIO_LED_READY_PIN', 24),
        'led_playing_pin' => (int) env('GPIO_LED_PLAYING_PIN', 25),
        'button_debounce_ms' => (int) env('GPIO_BUTTON_DEBOUNCE_MS', 180),
        'led_poll_interval_ms' => (int) env('GPIO_LED_POLL_INTERVAL_MS', 500),
        'player_state_db_path' => env('GPIO_PLAYER_STATE_DB_PATH', database_path('database.sqlite')),
    ],
];
