<?php

namespace App\Controller\Authorization;

use App\Controller\UserController;
use App\Helpers\CreateRefreshToken;
use App\Helpers\CreateToken;
use App\Helpers\DecodeToken;
use App\Models\User;
use App\Models\UserRefreshToken;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class LoginUser extends UserController
{
    public function action(): ResponseInterface
    {
        $data = json_decode($this->request->getBody()->getContents(), true);
        $user = User::findByUserEmail($data['email']);

        if (empty($data['email']))
            return $this->respondWithError(400, ['email' => 'Необходимо заполнить поле email']);

        if (empty($data['password']))
            return $this->respondWithError(400, ['password' => 'Необходимо заполнить поле Пароль']);

        if (is_null($user))
            return $this->respondWithError(400, ['email' => 'Пользователь с таким email не зарегистрирован']);

        try {

            if ($user->verifyPassword($data['password'])) {

                $tokenCount = UserRefreshToken::query()
                    ->where(['user_id' => $user['id']])
                    ->count();

                if ($tokenCount > 5) {
                    UserRefreshToken::query()
                        ->where([['user_id', '=', $user['id']]])
                        ->delete();
                }

                $token = CreateToken::action($user['id'], $this->container->get('jwt-secret'), $user['role']);
                $refrech_token = CreateRefreshToken::action($user['id'], $this->container->get('jwt-secret'), $user['role'], $data['fingerprint']);
                $token_info = DecodeToken::action($this->container->get('jwt-secret'), $refrech_token);

                $expires = $token_info['exp'];
                $cookie[] = "refreshToken=$refrech_token; path=/api/auth; domain=.{$_ENV['HOST']}; maxAge=$expires; expires=$expires; HttpOnly";
                return $this->respondWithData(['access_token' => $token, 'refresh_token' => $refrech_token], 200, $cookie);

            } else {
                return $this->respondWithData(['password' => 'Не верно указан пароль'],400);
            }

        } catch (Throwable $e) {
            return $this->respondWithError($e->getCode(), $e->getMessage());
        }
    }
}