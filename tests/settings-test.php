<?php

return [
    'settings' => [
        'displayErrorDetails' => true,
        'addContentLengthHeader' => false,
        'renderer' => [
            'template_path' => __DIR__ . '/../templates/',
        ],
        'logger' => [
            'name' => 'oky-vap-tango-gw-test',
            'path' => __DIR__ . '/../logs/app.log',
            'level' => \Monolog\Logger::DEBUG,
        ],
        'pdo' => [
            'dsn' => 'sqlite::memory:',
            'username' => null,
            'password' => null,
        ],
        'tango_auth' => [
            'api_url' => 'https://example.invalid',
            'account_identifier_tango' => 'test-account',
            'customer_identifier_tango' => 'test-customer',
            'password_tango' => 'test-password',
            'user_name_tango' => 'test-user',
        ],
    ],
];
