<?php
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/config/DotEnv.php';

// # include DB connection file
require '../src/config/Database.php';

$app = AppFactory::create();

// # include cookit routes
require '../src/helper/Authentication.php';
require '../src/helper/JsonResponse.php';
require '../src/routes/cookit-routes.php';

$app->run();