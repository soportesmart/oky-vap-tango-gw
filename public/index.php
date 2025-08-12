<?php
if (PHP_SAPI == 'cli-server') {
    // To help the built-in PHP dev server, check if the request was actually for
    // something which should probably be served as a static file
    $url  = parse_url($_SERVER['REQUEST_URI']);
    $file = __DIR__ . $url['path'];
    if (is_file($file)) {
        return false;
    }
}

require __DIR__ . '/../vendor/autoload.php';

session_start();

// Instantiate the app
//$settings = require __DIR__ . '/../src/settings.php';
$host = $_SERVER['HTTP_HOST'];
error_log('index.host : -->' . $host . '<--');
switch ($host) {
    case 'apimaster.oky.app':
        $settings = require __DIR__ . '/../app/settings-prod.php';
        break;
    case 'apidemo.oky.app':
        $settings = require __DIR__ . '/../app/settings-demo.php';
        break;
    case 'apiqa.oky.app':
        //error_log("QA : -->" . $host . "<--");
        $settings = require __DIR__ . '/../app/settings-qa.php';
        break;
    default:
        //error_log("Don't recognize the host : -->" . $host . "<--");
        $settings = require __DIR__ . '/../app/settings-qa.php';
        //$settings = require __DIR__ . '/../app/settings-dev.php';
}
$app = new \Slim\App($settings);

// Set up dependencies
require __DIR__ . '/../src/dependencies.php';

// Register middleware
require __DIR__ . '/../src/middleware.php';

// Register routes
require __DIR__ . '/../src/routes.php';

// Run app
$app->run();
