<?php

namespace App\Controller\Authorization;

use App\Controller\UserController;
use App\Helpers\CheckTokenExpiration;
use App\Helpers\CreateRefreshToken;
use App\Helpers\CreateToken;
use App\Helpers\DecodeToken;
use App\Models\User;
use App\Models\UserRefreshToken;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class RefreshToken extends UserController
{
    public function action(): ResponseInterface
    {
        $data = json_decode($this->request->getBody()->getContents(), true);
        $cookies = $this->request->getCookieParams();

        if (!array_key_exists('refreshToken', $cookies))
            return $this->respondWithError(219);

        try {

            $refresh = UserRefreshToken::findByToken($cookies['refreshToken']);
            if (is_null($refresh)) return $this->respondWithError(219);

            UserRefreshToken::query()
                ->where([['id', '=', $refresh['id']]])
                ->delete();

            if (!CheckTokenExpiration::action($this->container->get('jwt-secret'), $refresh['refresh_token'])
                || $refresh['fingerprint'] != $data['fingerprint']) {
                return $this->respondWithError(217);
            }

            $user = User::findOne($refresh['user_id']);
            $token = CreateToken::action($user['id'], $this->container->get('jwt-secret'), $user['role']);
            $refreshToken = CreateRefreshToken::action($user['id'], $this->container->get('jwt-secret'), $user['role'], $data['fingerprint']);
            $token_info = DecodeToken::action($this->container->get('jwt-secret'), $refreshToken);

            $expires = $token_info['exp'];
            $cookie[] = "refreshToken=$refreshToken; path=/api/auth; domain=.{$_ENV['HOST']}; maxAge=$expires; expires=$expires; HttpOnly";
            return $this->respondWithData(['access_token' => $token], 200, $cookie);

        } catch (Throwable $e) {
            return $this->respondWithError(219);
        }
    }
}