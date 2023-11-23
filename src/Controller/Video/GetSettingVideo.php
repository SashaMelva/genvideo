<?php

namespace App\Controller\Video;

use App\Controller\UserController;
use App\Helpers\CheckTokenExpiration;
use App\Models\ContentVideo;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class GetSettingVideo extends UserController
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function action(): ResponseInterface
    {
        $access_token = $this->request->getHeaderLine('Token');
        $contentId = $this->request->getAttribute('id');

        if (CheckTokenExpiration::action($this->container->get('jwt-secret'), $access_token)) {

            try {

                $dataSetting = [
                    'content-setting' => ContentVideo::findOne((int)$contentId),
                    'text-setting' =>
                    'logo-img' => ,
                'slide-show-img' => ,
                'sound' => ,
                'speeckit' => ,
                ];
                $content = ContentVideo::findByID((int)$contentId);
                return $this->respondWithData($content);

            } catch (Throwable $e) {
                return $this->respondWithError($e->getCode(), $e->getMessage());
            }
        } else {
            return $this->respondWithError(215);
        }
    }
}