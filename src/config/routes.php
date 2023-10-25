<?php


use App\Controller\Authorization\GetUserToken;
use App\Controller\Authorization\LoginUser;
use App\Controller\Authorization\LogoutUser;
use App\Controller\Authorization\RefreshToken;
use App\Controller\Authorization\RegistryUser;
use App\Controller\Files\DeleteAdditionalVideo;
use App\Controller\Files\DeleteImage;
use App\Controller\Files\DeleteMusic;
use App\Controller\Files\ImportDataForExcel;
use App\Controller\Files\UploadAdditionalVideo;
use App\Controller\Files\UploadImage;
use App\Controller\Files\UploadMusic;
use App\Controller\Integration\SaveTokenCallBack;
use App\Controller\Integration\TokenCallBack;
use App\Controller\Integration\CabinetItems;
use App\Controller\Integration\GetToken;
use App\Controller\Projects\AddUserProject;
use App\Controller\Projects\CreateProject;
use App\Controller\Projects\DeleteProject;
use App\Controller\Projects\DeleteUserProject;
use App\Controller\Projects\GetAllProject;
use App\Controller\Projects\GetInfoProject;
use App\Controller\Projects\UpdateProject;
use App\Controller\test\TestController;
use App\Controller\Users\ChangePassword;
use App\Controller\Users\ChangePasswordViaMail;
use App\Controller\Users\GetUserInfo;
use App\Controller\Video\CollectionDataVideo;
use App\Controller\Video\DownloadVideo;
use App\Controller\Video\GeneratorVideo;
use App\Controller\Video\GetAllContent;
use App\Controller\Video\GetCollectionDataSettingVideo;
use App\Controller\Video\GetContent;
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

        $group->get('/test', TestController::class);

        $group->group('/auth', function (RouteCollectorProxyInterface $group) {
            $group->post('/registry', RegistryUser::class);
            $group->post('/login', LoginUser::class);
            $group->post('/logout', LogoutUser::class);
            $group->get('/get-token/{id:[0-9]+}', GetUserToken::class);
            $group->post('/refresh-token', RefreshToken::class);
        });

        $group->group('/user', function (RouteCollectorProxyInterface $group) {
            $group->post('/password-recovery', ChangePasswordViaMail::class);
            $group->post('/change-password', ChangePassword::class);
            $group->get('/get-info', GetUserInfo::class);
        });

        $group->group('/project', function (RouteCollectorProxyInterface $group) {
            $group->get('/get-all', GetAllProject::class);
            $group->get('/get-info/{id:[0-9]+}', GetInfoProject::class);
            $group->post('/create', CreateProject::class);
            $group->post('/update', UpdateProject::class);
            $group->post('/delete', DeleteProject::class);
            $group->post('/add-user', AddUserProject::class);
            $group->post('/delete-user', DeleteUserProject::class);
        });

        $group->group('/video', function (RouteCollectorProxyInterface $group) {
            $group->post('/get-content-setting', GetCollectionDataSettingVideo::class);
            $group->post('/collection-data', CollectionDataVideo::class);
            $group->post('/get-all', GetAllContent::class);
            $group->get('/get/{id:[0-9]+}', GetContent::class);

            $group->get('/load/{id:[0-9]+}', DownloadVideo::class);

            $group->post('/send', SendVideo::class);
            //$group->get('/generate/{id:[0-9]+}', GeneratorVideo::class);
            $group->get('/test', TestController::class);


        });

        $group->group('/file', function (RouteCollectorProxyInterface $group) {
            $group->post('/add-image', UploadImage::class);
            $group->post('/add-music', UploadMusic::class);
            $group->post('/add-video', UploadAdditionalVideo::class);

            $group->post('/import/excel/', ImportDataForExcel::class);
//            $group->get('/delete-image/{id:[0-9]+}', DeleteImage::class);
//            $group->get('/delete-sound/{id:[0-9]+}', DeleteMusic::class);
//            $group->get('/delete-video/{id:[0-9]+}', DeleteAdditionalVideo::class);
        });

//        $group->group('/integration', function (RouteCollectorProxyInterface $group) {
            $group->get('/token/{id:[0-9]+}', GetToken::class);
            $group->get('/token-callback', TokenCallBack::class);
            $group->get('/add-token/{id:[0-9]+}', SaveTokenCallBack::class);
//        });

    });

    $app->addBodyParsingMiddleware();
};
