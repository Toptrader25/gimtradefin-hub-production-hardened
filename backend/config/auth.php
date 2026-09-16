<?php

use App\Models\User;

return [
    'defaults' => [
        'guard'     => 'web',
        'passwords' => 'users',
    ],

    'guards' => [
        'web' => [
            'driver'   => 'session',
            'provider' => 'users',
        ],
        // Note: routes protected with the `auth:sanctum` middleware use
        // the 'sanctum' guard, which Laravel\Sanctum's own service
        // provider registers automatically — it doesn't need an entry
        // here to work.
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model'  => User::class,
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table'    => 'password_reset_tokens',
            'expire'   => 60,   // minutes a reset token stays valid
            'throttle' => 60,   // seconds between reset-link requests for the same email
        ],
    ],

    'password_timeout' => 10800,
];
