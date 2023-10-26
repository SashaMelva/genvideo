<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Console\GeneratorChatGPTText;
use App\Console\GeneratorVideoCommand;
use App\Console\GetYandexAimToken;
use App\Console\ImportDataExcelCommand;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Application;


$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../', '.env');
$dotenv->load();

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
const DIRECTORY_EXCEL_IMPORT = PROJECT_DIR .  DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'excelImport' . DIRECTORY_SEPARATOR;
try {

    /** @var ContainerInterface $container */
    $container = require __DIR__ . '/../src/config/container.php';

    $cli = new Application('Console');
    (require __DIR__ . '/../src/config/eloquent_console.php')($cli, $container);

    $cli->add(new GeneratorVideoCommand());
    $cli->add(new GetYandexAimToken());
    $cli->add(new ImportDataExcelCommand());
    $cli->add(new GeneratorChatGPTText());

    $cli->run();

} catch (Throwable $exception) {
    echo $exception->getMessage();
    exit(1);
}