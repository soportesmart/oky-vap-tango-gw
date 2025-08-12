<?php
// Composer autoloading
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    /** @var Composer\Autoload\ClassLoader $loader */
    $loader = include __DIR__ . '/../vendor/autoload.php';
    $loader->addClassMap(array(
        'Security\Authentication' => __DIR__.'/../src/security/Authentication.php',
        'BusinessRules\TangoServiceBR' => __DIR__ .'/../src/rules/TangoServiceBR.php',
        'BusinessRules\GenericResponse' => __DIR__ .'/../src/rules/ApiResponse.php',
        'BusinessRules\ValidateNumberResponse' => __DIR__ .'/../src/rules/ApiResponse.php',
        'Messaging\MessagingEngine' => __DIR__ .'/../src/messaging/MessagingEngine.php',
        'Persistence\DBAccess' => __DIR__ .'/../src/persistence/DBAccess.php',
        'Persistence\DBQuery'  => __DIR__ .'/../src/persistence/DBQuery.php',
        'Persistence\DBApiSecurity'  => __DIR__ .'/../src/persistence/DBApiSecurity.php',
        'Util\CurlClient' => __DIR__ . '/../src/util/CurlClient.php',
    ));
}

