<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Console\DistributionChatGPTRequest;
use App\Console\FormatTextArticleFromChatGpt;
use App\Console\FormatTextFromChatGptCommand;
use App\Console\GeneratorChatGPTText;
use App\Console\GeneratorChatGPTTextPromise;
use App\Console\GeneratorImage;
use App\Console\GeneratorVideoCommand;
use App\Console\GeneratorVoiceAndSubtitlesCommand;
use App\Console\GetYandexAimToken;
use App\Console\ImportDataExcelCommand;
use App\Console\ImportExcelArticle;
use App\Console\NewTestGPTRequest;
use App\Console\SendingArticleWordpress;
use App\Console\TestGPTRequest;
use App\Console\TestScript;
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
const DIRECTORY_FONTS = PROJECT_DIR .  DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'fonts' . DIRECTORY_SEPARATOR;
const DIRECTORY_PREVIEW = PROJECT_DIR .  DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'preview' . DIRECTORY_SEPARATOR;
const DIRECTORY_EXCEL_IMPORT = PROJECT_DIR .  DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'excelImport' . DIRECTORY_SEPARATOR;
const DIRECTORY_ARCHIVE = PROJECT_DIR .  DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'archive' . DIRECTORY_SEPARATOR;
const DIRECTORY_RESULT_CONTENT = PROJECT_DIR . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'result' . DIRECTORY_SEPARATOR;
try {

    /** @var ContainerInterface $container */
    $container = require __DIR__ . '/../src/config/container.php';

    $cli = new Application('Console');
    (require __DIR__ . '/../src/config/eloquent_console.php')($cli, $container);

    $cli->add(new GeneratorVideoCommand()); //первый скрипт генерации видео на кроне
    $cli->add(new GetYandexAimToken()); // получение токена для синтеза текста на кроне
    $cli->add(new ImportDataExcelCommand()); //импорт данных с файлов excel на кроне
    $cli->add(new ImportExcelArticle()); // импорт данных для статей excel на кроне

    $cli->add(new DistributionChatGPTRequest()); //отправка запроса на получеие текста т чата gpt на кроне
    $cli->add(new GeneratorChatGPTText()); //отправка запроса на получеие текста т чата gpt на кроне

    $cli->add(new FormatTextArticleFromChatGpt()); //форматирование ответа, полученного от чата для статей на кроне
    $cli->add(new FormatTextFromChatGptCommand()); //форматирование ответа, полученного от чата на кроне
    $cli->add(new GeneratorImage()); // Получение картинок по описанию, полученного с чата
    $cli->add(new GeneratorVoiceAndSubtitlesCommand()); //генерация озвучки и субтитров ОТКЛЮЧЁН
    $cli->add(new SendingArticleWordpress()); // Публикация статей по апи вордпреса


    $cli->add(new NewTestGPTRequest());
    $cli->add(new GeneratorChatGPTTextPromise());
    $cli->add(new TestGPTRequest());
    $cli->add(new TestScript());

    $cli->run();

} catch (Throwable $exception) {
    echo $exception->getMessage();
    exit(1);
}