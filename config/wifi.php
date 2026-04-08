<?php

return [
    'hotspot' => [
        'connection_name' => env('WIFI_HOTSPOT_CONNECTION_NAME', 'tonpi-hotspot'),
        'ssid' => env('WIFI_HOTSPOT_SSID', 'TonPI-Setup'),
        'password' => env('WIFI_HOTSPOT_PASSWORD', 'tonpi-setup-123'),
        'interface' => env('WIFI_INTERFACE', 'wlan0'),
    ],
];
