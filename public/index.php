<?php

use Slim\Factory\AppFactory;
use DI\ContainerBuilder;

require __DIR__ . '/../vendor/autoload.php';

define('PROJECT_DIR', dirname(__DIR__));
const DIRECTORY_IMG = PROJECT_DIR . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'resourses' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR;
const DIRECTORY_MAIN_IMG = PROJECT_DIR . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'resourses' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR;
const DIRECTORY_MUSIC = PROJECT_DIR . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'resourses' . DIRECTORY_SEPARATOR . 'music' . DIRECTORY_SEPARATOR;
const DIRECTORY_VIDEO = PROJECT_DIR . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'video' . DIRECTORY_SEPARATOR;
const DIRECTORY_MAIN_VIDEO = PROJECT_DIR . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'video' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR;

try {

    $builder = new ContainerBuilder();
    $builder->addDefinitions(require __DIR__ . '/../src/config/dependencies.php');

    $container = $builder->build();

    $app = AppFactory::createFromContainer($container);
    $app->addBodyParsingMiddleware();

    (require __DIR__ . '/../src/config/cors.php')($app);
    $app->addRoutingMiddleware();

    (require __DIR__ . '/../src/config/middleware.php')($app, $container);
    (require __DIR__ . '/../src/config/routes.php')($app);
    (require __DIR__ . '/../src/config/eloquent.php')($app);

    $app->run();
} catch (Exception $e) {
}