<?php

return [
    'reader_command' => env('RFID_READER_COMMAND'),
    'read_once_command' => env('RFID_READ_ONCE_COMMAND'),
    'debounce_seconds' => (int) env('RFID_DEBOUNCE_SECONDS', 2),
];
