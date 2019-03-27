<?php

return [
    /*
    |--------------------------------------------------------------------------
    | MT5 Credentials
    |--------------------------------------------------------------------------
    |
    | this is credentials for access to the mt5 server WebApi account
    |
    */
    'mt5' => [
        'ip' => env('MT5_SERVER_IP', '127.0.0.1'),
        'port' => env('MT5_SERVER_PORT', 443),
        'login' => env('MT5_SERVER_WEB_LOGIN', ''),
        'password' => env('MT5_SERVER_WEB_PASSWORD', ''),
    ]
];