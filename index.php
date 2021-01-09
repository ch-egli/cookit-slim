<?php
/*
 * This is the production file in the root (not public!) directory.
 */

use Slim\Factory\AppFactory;

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/src/config/DotEnv.php';

// # include DB connection file
require 'src/config/Database.php';

$app = AppFactory::create();
$app->addBodyParsingMiddleware();

// Enable CORS
$app->options('/{routes:.+}', function ($request, $response, $args) {
    return $response;
});
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    $response = $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', '*')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
    $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
    return $response;
});


// # include cookit routes
require 'src/repo/RecipeRepo.php';
require 'src/helper/Authentication.php';
require 'src/helper/JsonResponse.php';
require 'src/routes/cookit-routes.php';

$app->run();
