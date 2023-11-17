<?php

use Slim\Factory\AppFactory;
use DI\ContainerBuilder;

require __DIR__ . '/../vendor/autoload.php';


$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../', '.env');
$dotenv->load();

session_start();

define('PROJECT_DIR', dirname(__DIR__));
const DIRECTORY_IMG = PROJECT_DIR .  DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR;
define("RELATIVE_PATH_IMG", $_ENV['HOST'] . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR);
const DIRECTORY_BACKGROUND = PROJECT_DIR .  DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR. 'background' . DIRECTORY_SEPARATOR;
define("RELATIVE_PATH_BACKGROUND", $_ENV['HOST'] . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'background' . DIRECTORY_SEPARATOR);
const DIRECTORY_LOGO_IMG = PROJECT_DIR .  DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'logo' . DIRECTORY_SEPARATOR;
define("RELATIVE_PATH_LOGO_IMG", $_ENV['HOST'] . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'logo' . DIRECTORY_SEPARATOR);
const DIRECTORY_MUSIC = PROJECT_DIR .  DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'music' . DIRECTORY_SEPARATOR . 'sound' . DIRECTORY_SEPARATOR;
define("RELATIVE_PATH_MUSIC", $_ENV['HOST'] . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'music' . DIRECTORY_SEPARATOR . 'sound' . DIRECTORY_SEPARATOR);
const DIRECTORY_SPEECHKIT = PROJECT_DIR .  DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'music' . DIRECTORY_SEPARATOR . 'speechkit' . DIRECTORY_SEPARATOR;
define("RELATIVE_PATH_SPEECHKIT", $_ENV['HOST'] . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'music' . DIRECTORY_SEPARATOR . 'speechkit' . DIRECTORY_SEPARATOR);
const DIRECTORY_VIDEO = PROJECT_DIR .  DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'video' . DIRECTORY_SEPARATOR;
define("RELATIVE_PATH_VIDEO", $_ENV['HOST'] . DIRECTORY_SEPARATOR . 'video' . DIRECTORY_SEPARATOR);

const DIRECTORY_ADDITIONAL_VIDEO = PROJECT_DIR .  DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'video' . DIRECTORY_SEPARATOR;
define("RELATIVE_PATH_ADDITIONAL_VIDEO", $_ENV['HOST'] . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'video' . DIRECTORY_SEPARATOR);

const DIRECTORY_TEXT = PROJECT_DIR .  DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'subtitles' . DIRECTORY_SEPARATOR;
define("RELATIVE_PATH_TEXT", $_ENV['HOST'] . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'subtitles' . DIRECTORY_SEPARATOR);
const DIRECTORY_CLIENT_SECRET = PROJECT_DIR .  DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'client_secret'  . DIRECTORY_SEPARATOR;
const DIRECTORY_EXCEL_IMPORT = PROJECT_DIR .  DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'excelImport' . DIRECTORY_SEPARATOR;
const DIRECTORY_FONTS = PROJECT_DIR .  DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'fonts' . DIRECTORY_SEPARATOR;
const DIRECTORY_PREVIEW = PROJECT_DIR .  DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'preview' . DIRECTORY_SEPARATOR;
const DIRECTORY_RESULT_CONTENT = PROJECT_DIR . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'result' . DIRECTORY_SEPARATOR;

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