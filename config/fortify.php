<?php

use Laravel\Fortify\Features;

$applicationUrl = rtrim((string) config('app.url'), '/');
$applicationHost = parse_url($applicationUrl, PHP_URL_HOST);

return [

    'guard' => 'web',
    'passwords' => 'users',
    'username' => 'email',
    'email' => 'email',
    'lowercase_usernames' => true,
    'home' => '/dashboard',
    'prefix' => '',
    'domain' => null,
    'middleware' => ['web'],

    'limiters' => [
        'login' => 'login',
        'two-factor' => 'two-factor',
        'passkeys' => 'passkeys',
    ],

    'views' => true,

    'passkeys' => [
        'relying_party_id' => env('PASSKEYS_RELYING_PARTY_ID', $applicationHost),
        'allowed_origins' => [
            rtrim((string) env('PASSKEYS_ORIGIN', $applicationUrl), '/'),
        ],
        'user_handle_secret' => env('PASSKEYS_USER_HANDLE_SECRET', config('app.key')),
        'timeout' => 60000,
    ],

    'features' => [
        Features::registration(),
        Features::emailVerification(),
        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
        ]),
        Features::passkeys([
            'confirmPassword' => true,
        ]),
    ],

];
