<?php
// DIC configuration

$container = $app->getContainer();

// view renderer
$container['renderer'] = function ($c) {
    $settings = $c->get('settings')['renderer'];
    return new Slim\Views\PhpRenderer($settings['template_path']);
};

// monolog
$container['logger'] = function ($c) {
    $settings = $c->get('settings')['logger'];
    $logger = new Monolog\Logger($settings['name']);
    $logger->pushProcessor(new Monolog\Processor\UidProcessor());
    $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'], $settings['level']));
    return $logger;
};

// PDO
$container['pdo'] = function ($c) {
    $settings = $c->get('settings')['pdo'];
    $pdo = new \PDO($settings['dsn'], $settings['username'], $settings['password']);
    $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    return $pdo;
};

// Tango
$container['tango_auth'] = function ($c) {
    $settings = $c->get('settings')['tango_auth'];
    $auth = new stdClass();
    $auth->apiUrl = $settings['api_url'];
    $auth->accountIdentifierTango = $settings['account_identifier_tango'];
    $auth->customerIdentifierTango = $settings['customer_identifier_tango'];
    $auth->passwordTango = $settings['password_tango'];
    $auth->userNameTango = $settings['user_name_tango'];
    return $auth;
};

$container['dbquery'] = function ($c) {
    $dbquery  = new \Persistence\DBQuery($c['pdo'], $c['logger']);
    return $dbquery;
};

$container['dbapisecurity'] = function ($c) {
    $dbapisecurity  = new \Persistence\DBApiSecurity($c['pdo'], $c['logger']);
    return $dbapisecurity;
};

$container['messaging'] = function ($c) {
    $messagingengine  = new \Messaging\MessagingEngine($c['dbquery'], $c['curl'], $c['logger'], $c['oky_comm_auth']);
    return $messagingengine;
};

$container['tangoservicebr'] = function ($c) {
    $tangoservicebr  = new \BusinessRules\TangoServiceBR($c['dbquery'], $c['logger'], $c['curl'], $c['tango_auth']);
    return $tangoservicebr;
};

$container['curl'] = function ($c) {
    $curl  = new \Util\CurlClient($c['logger']);
    return $curl;
};

$container['authentication'] = function ($c) {
    $authentication  = new \Security\Authentication($c['dbapisecurity'], $c['logger']);
    return $authentication;
};
