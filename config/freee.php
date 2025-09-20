<?php

return [
    /*
    |--------------------------------------------------------------------------
    | freee API Configuration
    |--------------------------------------------------------------------------
    |
    | ここにfreee APIの設定を記述します。
    | 実際の値は.envファイルで設定してください。
    |
    */

    'client_id' => env('FREEE_CLIENT_ID'),
    'client_secret' => env('FREEE_CLIENT_SECRET'),
    'redirect_uri' => env('FREEE_REDIRECT_URI', env('APP_URL') . '/api/freee/callback'),
    'base_url' => env('FREEE_BASE_URL', 'https://api.freee.co.jp'),
];
