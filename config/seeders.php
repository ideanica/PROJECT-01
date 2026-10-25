<?php

return [
    'admin' => [
        'name' => env('INITIAL_ADMIN_NAME', env('APP_ENV') === 'production' ? null : 'OceanPaws Admin'),
        'username' => env('INITIAL_ADMIN_USERNAME'),
        'email' => env('INITIAL_ADMIN_EMAIL', env('APP_ENV') === 'production' ? null : 'admin@oceanpaws.test'),
        'password' => env('INITIAL_ADMIN_PASSWORD', env('APP_ENV') === 'production' ? null : 'password'),
    ],
    'demo_customer_email' => env('OCEANPAWS_DEMO_CUSTOMER_EMAIL', 'customer.demo@oceanpaws.test'),
    'demo_customer_password' => env('OCEANPAWS_DEMO_CUSTOMER_PASSWORD', env('APP_ENV') === 'production' ? null : 'password'),
];
