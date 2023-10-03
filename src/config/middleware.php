<?php

use Psr\Container\ContainerInterface;
use Slim\App;

return static function(App $app, ContainerInterface $container):void {
    $app->addErrorMiddleware(true, false, false);
    $app->add(new Tuupola\Middleware\JwtAuthentication([
        'algorithm' => 'HS256',
        "path" => ["/api/change-password"],
        /*игнорирование для тестового сервера*/
        "ignore" => ["/api/change-password"],
        "secure" => false,
        "relaxed" => ["scan-rest"],
        "secret" => $container->get('jwt-secret')
    ]));
};
