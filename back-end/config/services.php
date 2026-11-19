<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
     * TheSportsDB player cut-outs, looked up while seeding. Key "123" is the public
     * test key (the old "3" key is disabled). Set THESPORTSDB_FETCH=false to seed
     * offline from the cached lookups in database/seeders/data/player_cutouts.json.
     */
    'thesportsdb' => [
        'key' => env('THESPORTSDB_KEY', '123'),
        'fetch' => (bool) env('THESPORTSDB_FETCH', true),
        // Verified lookups (football:sync-tsdb); null = database/seeders/data/player_cutouts.json.
        'cache' => env('THESPORTSDB_CACHE'),
        // Accepted cut-outs are copied to storage/app/public/cutouts and served from here.
        'local_cutouts' => (bool) env('THESPORTSDB_LOCAL_CUTOUTS', true),
        'cutouts_url' => env('CUTOUTS_URL', '/storage/cutouts'),
    ],

];
