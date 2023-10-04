<?php


use App\Controller\Authorization\GetUserToken;
use App\Controller\Authorization\LoginUser;
use App\Controller\Authorization\RefreshToken;
use App\Controller\Authorization\RegistryUser;
use App\Controller\Files\DeleteAdditionalVideo;
use App\Controller\Files\DeleteImage;
use App\Controller\Files\DeleteMusic;
use App\Controller\Files\UploadAdditionalVideo;
use App\Controller\Files\UploadImage;
use App\Controller\Files\UploadMusic;
use App\Controller\test\TestController;
use App\Controller\Video\CollectionDataVideo;
use App\Controller\Video\DownloadVideo;
use App\Controller\Video\GeneratorVideo;
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

            $group->post('/generate', GeneratorVideo::class);
            $group->get('/test', TestController::class);

            $group->post('/download', DownloadVideo::class);
            $group->post('/send', SendVideo::class);
        });

        $group->group('/file', function (RouteCollectorProxyInterface $group) {
            $group->post('/add-image', UploadImage::class);
            $group->post('/add-music', UploadMusic::class);
            $group->post('/add-additional-video', UploadAdditionalVideo::class);

            $group->get('/delete-image/{id:[0-9]+}', DeleteImage::class);
            $group->get('/delete-music/{id:[0-9]+}', DeleteMusic::class);
            $group->get('/delete-additional-video/{id:[0-9]+}', DeleteAdditionalVideo::class);
        });

    });

    $app->addBodyParsingMiddleware();
};
