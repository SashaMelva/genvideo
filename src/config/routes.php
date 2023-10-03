<?php


use App\Controller\Authorization\GetUserToken;
use App\Controller\Authorization\LoginUser;
use App\Controller\Authorization\RefreshToken;
use App\Controller\Authorization\RegistryUser;
use App\Controller\test\TestController;
use App\Controller\Video\CollectionDataVideo;
use App\Controller\Video\DownloadVideo;
use App\Controller\Video\ImageDataVideo;
use App\Controller\Video\MusicDataVideo;
use App\Controller\Video\SendVideo;
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
            $group->get('/registry', RegistryUser::class);
            $group->get('/login', LoginUser::class);
            $group->get('/refresh', RefreshToken::class);
            $group->get('/get-token/{id:[0-9]+}', GetUserToken::class);
        });
        $group->get('/test', TestController::class);

        $group->group('/video', function (RouteCollectorProxyInterface $group) {
            $group->post('/collection-data', CollectionDataVideo::class);
            $group->post('/image-data', ImageDataVideo::class);
            $group->post('/music-data', MusicDataVideo::class);

            $group->get('/test', TestController::class);

            $group->post('/download', DownloadVideo::class);
            $group->post('/send', SendVideo::class);
        });

    });

    $app->addBodyParsingMiddleware();
};
