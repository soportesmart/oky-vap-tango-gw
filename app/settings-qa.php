<?php
return [
    'settings' => [
        'displayErrorDetails' => true, // set to false in production
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header

        // Renderer settings
        'renderer' => [
            'template_path' => __DIR__ . '/../templates/',
        ],

	// Monolog settings
        'logger' => [
            'name' => 'slim-app',
            'path' => __DIR__ . '/../logs/app.log',
            'path' => '/tmp/oky-tango-gw.log',
            'level' => \Monolog\Logger::DEBUG,
        ],

       	//PDO Settings
        'pdo' => [
            'dsn' => 'mysql:host=35.94.91.106;dbname=oky_release;charset=utf8',
            'username' => 'develop',
            'password' => 'smart2360',
        ],

	//Oky Communications Settings
        'oky_comm_auth' => [
            'api_url' => 'https://api-qa.oky.app/oky-communication-gw/notification/',
            'api_token' => 'e6cc90b878b948c35e92b003c792c46c58c4af40',
            'sender_mail' => 'no-reply@okyapp.com',
            'sender_name' => 'Oky'
        ],

       	//Numverify Settings
        'numverify_auth' => [
            'api_url' => 'http://apilayer.net/api/validate',
            'access_key' => '86ee5a58918303b609bf252a55e0ad43'
        ],

       	//Twilio Settings

	//Tango Settings
        'tango_auth' => [
            'api_url' => 'https://integration-api.tangocard.com/raas/v2/orders',
            'account_identifier_tango' => 'A70259346',
            'customer_identifier_tango' => 'G13614836',
            'password_tango' => 'EWWx$CH@MLQVBmaym@NyQnFyjuRJycOcocKzopIjvjP',
            'user_name_tango' => 'OKYApp-TEST'
        ],

       	//Rest Countries Settings
        'countries_auth' => [
            'api_url' => 'https://restcountries.eu/rest/v2/alpha/'
        ],

       	//IP Quality Store Settings
        'ipqs_auth' => [
            'api_url' => 'https://ipqualityscore.com/api/json/phone/',
            'api_key' => 'WXTm6a4tg4RM8XRav6bNwOTqn2sgNUcO'
        ]
    ],
];
