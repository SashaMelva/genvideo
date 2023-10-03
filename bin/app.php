<?php

use DI\ContainerBuilder;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;

require_once __DIR__ . '/../vendor/autoload.php';

$env = (new ArgvInput())->getParameterOption(['--env', '-e'], 'dev');

if ($env) {
    $_ENV['APP_ENV'] = $env;
}

//config container
$builder= new ContainerBuilder();
$builder->addDefinitions(require __DIR__.'/../src/config/dependencies.php');

$container = $builder->build();

try {
    /** @var Application $application */
    $application = $container->get(Application::class);
    (require __DIR__.'/../src/config/eloquent_console.php')($application, $container);

    exit($application->run());
} catch (Throwable $exception) {
    echo $exception->getMessage();
    exit(1);
}