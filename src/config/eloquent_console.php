<?php

use DI\Container;
use Symfony\Component\Console\Application;

return static function (Application $app, Container $container){
    $capsule = new Illuminate\Database\Capsule\Manager;
    $dbSettings = $container->get($container->get('keyIp'));
    $capsule->addConnection($dbSettings);
    $capsule->bootEloquent();
    $capsule->setAsGlobal();
};