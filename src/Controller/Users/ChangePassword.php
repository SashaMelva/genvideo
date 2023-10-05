<?php

namespace App\Controller\Users;

use App\Controller\UserController;
use App\Helpers\CheckTokenExpiration;
use App\Models\User;
use App\Models\UserRefreshToken;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class ChangePassword extends UserController
{
    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function action(): ResponseInterface
    {
        $data = json_decode($this->request->getBody()->getContents(), true);

        if (CheckTokenExpiration::action($this->container->get('jwt-secret'), $data['token'])) {

            try {
                $token = JWT::decode($data['token'], new Key($this->container->get('jwt-secret'), 'HS256'));

                $user = User::findOne($token->user_id);
                $user->setAttribute('password',$data['password']);
                $user->setAttribute('password_hash', password_hash($data['password'], PASSWORD_DEFAULT));
                $user->update();

                # удаляем все токены
                UserRefreshToken::query()->where([['user_id', '=', $token->user_id]])->delete();
                return $this->respondWithData('Success');

            } catch (Throwable $e) {
                return $this->respondWithError($e->getCode(), $e->getMessage());
            }

        } else {
            return $this->respondWithError(215);
        }
    }
}