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
            $token = $_SESSION['access_token'];
            $_SESSION['access_token'] = '';

            if (TokenYoutube::checkToken($userId)) {
                TokenYoutube::updateToken($userId, json_encode($token));
                return $this->respondWithData('Токен обновлён');

            } else {
                TokenYoutube::addToken($userId, json_encode($token));
                return $this->respondWithData('Токен добавлен');
            }

        } catch (Throwable $e) {
            return $this->respondWithError($e->getCode(), $e->getMessage());
        }
    }
}