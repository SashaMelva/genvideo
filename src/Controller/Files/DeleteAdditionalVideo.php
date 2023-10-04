<?php

namespace App\Controller\Files;

use App\Controller\UserController;
use App\Helpers\CheckTokenExpiration;
use App\Models\AdditionalVideo;
use App\Models\ListAdditionalVideo;
use App\Models\User;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Http\Message\ResponseInterface;

class DeleteAdditionalVideo extends UserController
{
    public function action(): ResponseInterface
    {
        $access_token = $this->request->getHeaderLine('token');
        $videoId = $this->request->getAttribute('id');

        if (CheckTokenExpiration::action($this->container->get('jwt-secret'), $access_token)) {

            try {
                $token = JWT::decode($access_token, new Key($this->container->get('jwt-secret'), 'HS256'));
                $user = User::query()->findOrFail($token->user_id);

                if ($user->isUser()) {

                    $video = AdditionalVideo::findOne($videoId);
                    unlink(DIRECTORY_MUSIC . $video->name);
                    ListAdditionalVideo::deleteVideo($videoId);
                    AdditionalVideo::deleteVideo($videoId);

                    return $this->respondWithData('Success');
                } else {
                    return $this->respondWithError(221);
                }

            } catch (Exception $e) {
                return $this->respondWithError($e->getCode(), $e->getMessage());
            }
        } else {
            return $this->respondWithError(215);
        }
    }
}