<?php


use App\Controller\test\TestController;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface;

return static function(App $app):void
{

    $app->options('/api/{routes:.+}', function (RequestInterface $request, ResponseInterface $response): ResponseInterface {
        return $response;
    });

    $app->group('/api', function (RouteCollectorProxyInterface $group) {

        $group->group('/auth', function (RouteCollectorProxyInterface $group) {
        });
        $group->get('/test', TestController::class);

    });

    $app->addBodyParsingMiddleware();
};
