<?php

namespace App\Controller\Integration;

use App\Controller\UserController;
use App\Models\TokenYoutube;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class SaveTokenCallBack extends UserController
{
    public function action(): ResponseInterface
    {
        try {

            $userId = $this->request->getAttribute('id');
            $accessToken = $_SESSION['access_token'];
            $refreshToken = $_SESSION['refresh_token'];
            $_SESSION['access_token'] = '';
            $_SESSION['refresh_token'] = '';

            if (TokenYoutube::checkToken($userId)) {
                TokenYoutube::updateToken($userId, json_encode($accessToken), json_encode($refreshToken));
                return $this->respondWithData('Токен обновлён');

            } else {
                TokenYoutube::addToken($userId, json_encode($accessToken), json_encode($refreshToken));
                return $this->respondWithData('Токен добавлен');
            }

//            $redirect_uri = 'http://localhost:8080/api/add-token/' . $userId;
//            return $this->response->withHeader('Location', filter_var($redirect_uri, FILTER_SANITIZE_URL));
        } catch (Throwable $e) {
            return $this->respondWithError($e->getCode(), $e->getMessage());
        }
    }
}