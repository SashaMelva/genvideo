<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Console\GeneratorVideoCommand;
use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;


$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../', '.env');
$dotenv->load();

try {

    /** @var ContainerInterface $container */
    $container = require __DIR__ . '/../src/Config/container.php';

    $cli = new Application('Console');
    (require __DIR__ . '/../src/Config/eloquent_console.php')($cli, $container);

    $cli->add(new GeneratorVideoCommand());

    $cli->run();

} catch (Throwable $exception) {
    echo $exception->getMessage();
    exit(1);
}