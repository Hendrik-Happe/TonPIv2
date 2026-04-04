<?php

return [
    'reader_command' => env('RFID_READER_COMMAND'),
    'debounce_seconds' => (int) env('RFID_DEBOUNCE_SECONDS', 2),
];
