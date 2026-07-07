<?php

return [
    'base_url' => env('OPERATON_BASE_URL', 'http://127.0.0.1:58080/engine-rest'),
    'web_url' => env('OPERATON_WEB_URL', 'http://127.0.0.1:58080/operaton'),
    'username' => env('OPERATON_USERNAME', 'demo'),
    'password' => env('OPERATON_PASSWORD', 'demo'),
    'timeout' => (int) env('OPERATON_TIMEOUT', 10),
    'default_history_ttl' => (int) env('OPERATON_DEFAULT_HISTORY_TTL', 180),
];
