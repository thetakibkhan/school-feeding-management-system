<?php

return [
    'accounts' => [
        'admin' => [
            'email' => env('ASSESSMENT_ADMIN_EMAIL'),
            'password' => env('ASSESSMENT_ADMIN_PASSWORD'),
        ],
        'field_staff' => [
            'email' => env('ASSESSMENT_FIELD_EMAIL'),
            'password' => env('ASSESSMENT_FIELD_PASSWORD'),
        ],
    ],
];
