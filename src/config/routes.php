<?php


use App\Controller\Article\AddNewWebsite;
use App\Controller\Article\DeletedWebsite;
use App\Controller\Article\GetAllArticle;
use App\Controller\Article\GetAllWebsite;
use App\Controller\Article\GetByIdArticle;
use App\Controller\Article\GetOneWebsite;
use App\Controller\Article\UpdateWebsite;
use App\Controller\Authorization\GetUserToken;
use App\Controller\Authorization\LoginUser;
use App\Controller\Authorization\LogoutUser;
use App\Controller\Authorization\RefreshToken;
use App\Controller\Authorization\RegistryUser;
use App\Controller\Files\DeleteAdditionalVideo;
use App\Controller\Files\DeleteImage;
use App\Controller\Files\DeleteMusic;
use App\Controller\Files\GetTypeImage;
use App\Controller\Files\GetTypeMusic;
use App\Controller\Files\GetTypeVideo;
use App\Controller\Files\ImportDataForExcel;
use App\Controller\Files\LoadMusic;
use App\Controller\Files\LoadVideo;
use App\Controller\Files\UploadAdditionalVideo;
use App\Controller\Files\UploadImage;
use App\Controller\Files\UploadMusic;
use App\Controller\Integration\SaveTokenCallBack;
use App\Controller\Integration\TokenCallBack;
use App\Controller\Integration\GetToken;
use App\Controller\Projects\AddUserProject;
use App\Controller\Projects\CreateProject;
use App\Controller\Projects\DeleteProject;
use App\Controller\Projects\DeleteUserProject;
use App\Controller\Projects\GetAllImageProject;
use App\Controller\Projects\GetAllMusicProject;
use App\Controller\Projects\GetAllProject;
use App\Controller\Projects\GetAllVideoProject;
use App\Controller\Projects\GetInfoProject;
use App\Controller\Projects\UpdateProject;
use App\Controller\test\TestController;
use App\Controller\test\TestGPT;
use App\Controller\Users\ChangePassword;
use App\Controller\Users\ChangePasswordViaMail;
use App\Controller\Users\GetUserInfo;
use App\Controller\Video\CollectionDataVideo;
use App\Controller\Video\CorrectionErrorsVideoGeneration;
use App\Controller\Video\DownloadVideo;
use App\Controller\Video\GetAllContent;
use App\Controller\Video\GetCollectionDataSettingVideo;
use App\Controller\Video\GetContent;
use App\Controller\Video\GetSettingVideo;
use App\Controller\Video\GetVideoArchive;
use App\Controller\Video\LoadPreviewVideo;
use App\Controller\Video\SendVideo;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface;

return static function (App $app): void {

    $app->options('/api/{routes:.+}', function (RequestInterface $request, ResponseInterface $response): ResponseInterface {
        return $response;
    });

    $app->group('/api', function (RouteCollectorProxyInterface $group) {

        $group->get('/test', TestController::class);
        $group->get('/test-GPT', TestGPT::class);

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


            $group->get('/get-all/image/{id:[0-9]+}', GetAllImageProject::class);
            $group->get('/get-all/music/{id:[0-9]+}', GetAllMusicProject::class);
            $group->get('/get-all/video/{id:[0-9]+}', GetAllVideoProject::class);
        });

        $group->group('/video', function (RouteCollectorProxyInterface $group) {
            $group->post('/get-content-setting', GetCollectionDataSettingVideo::class);
            $group->post('/collection-data', CollectionDataVideo::class);
            $group->post('/get-all', GetAllContent::class);
            $group->get('/get/{id:[0-9]+}', GetContent::class);
            $group->get('/load/{id:[0-9]+}/{token}', DownloadVideo::class);
            $group->get('/load/preview/{id:[0-9]+}', LoadPreviewVideo::class);

//            $group->post('/send', SendVideo::class);
            //$group->get('/generate/{id:[0-9]+}', GeneratorVideo::class);
//            $group->get('/test', TestController::class);

            $group->get('/correction-errors/{id:[0-9]+}', CorrectionErrorsVideoGeneration::class);
            $group->get('/get-setting/{id:[0-9]+}', GetSettingVideo::class);
        });

        $group->group('/file', function (RouteCollectorProxyInterface $group) {
            $group->post('/add-image', UploadImage::class);
            $group->post('/add-music', UploadMusic::class);
            $group->post('/add-video', UploadAdditionalVideo::class);

            $group->get('/load-archive/{id}/{type}/{token}', GetVideoArchive::class);
            $group->get('/load-video/{id:[0-9]+}/{token}', LoadVideo::class);
            $group->get('/load-music/{id:[0-9]+}', LoadMusic::class);

            $group->post('/import/excel', ImportDataForExcel::class);

            $group->get('/type/image', GetTypeImage::class);
            $group->get('/type/music', GetTypeMusic::class);
            $group->get('/type/video', GetTypeVideo::class);
//            $group->get('/delete-image/{id:[0-9]+}', DeleteImage::class);
//            $group->get('/delete-sound/{id:[0-9]+}', DeleteMusic::class);
//            $group->get('/delete-video/{id:[0-9]+}', DeleteAdditionalVideo::class);
        });

        $group->group('/website', function (RouteCollectorProxyInterface $group) {
            $group->get('/get-one/{id:[0-9]+}', GetOneWebsite::class);
            $group->get('/get-all', GetAllWebsite::class);
            $group->post('/add', AddNewWebsite::class);
            $group->post('/update', UpdateWebsite::class);
            $group->get('/delete/{id:[0-9]+}', DeletedWebsite::class);
        });

        $group->group('/article', function (RouteCollectorProxyInterface $group) {
            $group->get('/get-all/{id:[0-9]+}', GetAllArticle::class);
            $group->get('/get/{id:[0-9]+}', GetByIdArticle::class);
        });

//        $group->group('/integration', function (RouteCollectorProxyInterface $group) {
        $group->get('/token/{id:[0-9]+}', GetToken::class);
        $group->get('/token-callback', TokenCallBack::class);
        $group->get('/add-token/{id:[0-9]+}', SaveTokenCallBack::class);
//        });

    });

    $app->addBodyParsingMiddleware();
};
