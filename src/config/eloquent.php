<?php

use Slim\App;

return static function (App $app){
    $container = $app->getContainer();
    $dbSettings = $container->get($container->get('keyIp'));
    $capsule = new Illuminate\Database\Capsule\Manager;
    $capsule->addConnection($dbSettings);
    $capsule->bootEloquent();
    $capsule->setAsGlobal();
};