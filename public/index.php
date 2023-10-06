<?php

use Slim\Factory\AppFactory;
use DI\ContainerBuilder;

require __DIR__ . '/../vendor/autoload.php';

define('PROJECT_DIR', dirname(__DIR__));
const DIRECTORY_IMG = PROJECT_DIR . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR;
const RELATIVE_PATH_IMG = 'genvideo.loc:8080' . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR;

const DIRECTORY_BACKGROUND = PROJECT_DIR . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR. 'background' . DIRECTORY_SEPARATOR;
const RELATIVE_PATH_BACKGROUND = 'genvideo.loc:8080' . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR. 'background' . DIRECTORY_SEPARATOR;
const DIRECTORY_LOGO_IMG = PROJECT_DIR . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'logo' . DIRECTORY_SEPARATOR;
const RELATIVE_PATH_LOGO_IMG = 'genvideo.loc:8080' . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'logo' . DIRECTORY_SEPARATOR;
const DIRECTORY_MUSIC = PROJECT_DIR . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'music' . DIRECTORY_SEPARATOR . 'sound' . DIRECTORY_SEPARATOR;
const RELATIVE_PATH_MUSIC = 'genvideo.loc:8080' . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'music' . DIRECTORY_SEPARATOR . 'sound' . DIRECTORY_SEPARATOR;
const DIRECTORY_SPEECHKIT = PROJECT_DIR . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'music' . DIRECTORY_SEPARATOR . 'speechkit' . DIRECTORY_SEPARATOR;
const RELATIVE_PATH_SPEECHKIT = 'genvideo.loc:8080' . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'music' . DIRECTORY_SEPARATOR . 'speechkit' . DIRECTORY_SEPARATOR;
const DIRECTORY_VIDEO = PROJECT_DIR . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'video' . DIRECTORY_SEPARATOR;
const RELATIVE_PATH_VIDEO = 'genvideo.loc:8080' . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'video' . DIRECTORY_SEPARATOR;

const DIRECTORY_ADDITIONAL_VIDEO = PROJECT_DIR . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'video' . DIRECTORY_SEPARATOR;
const RELATIVE_PATH_ADDITIONAL_VIDEO = 'genvideo.loc:8080' . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'video' . DIRECTORY_SEPARATOR;


const DIRECTORY_MAIN_VIDEO = PROJECT_DIR . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'video' . DIRECTORY_SEPARATOR . 'logo' . DIRECTORY_SEPARATOR;
const DIRECTORY_TEXT = PROJECT_DIR . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'subtitles' . DIRECTORY_SEPARATOR;
const RELATIVE_PATH_TEXT = 'genvideo.loc:8080' . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'subtitles' . DIRECTORY_SEPARATOR;

define('SPEECHKIT_CLOUD_API', 'feca5b7a-f5f8-4bfa-886c-203788aa86c4');
define('CAPTCHA_KEY', 'f5cf0a7dda3d34321cccd2d584ece075');

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../', '.env');
$dotenv->load();

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