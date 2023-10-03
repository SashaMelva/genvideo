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
use function Sentry\captureException;

class RefreshToken extends UserController
{
    public function action(): ResponseInterface
    {
        $data = $this->getFormData();
        $cookies = $this->request->getCookieParams();

        if (!array_key_exists('refreshToken', $cookies))
            return $this->respondWithError(219);

        try {

            $refrech = UserRefreshToken::findByToken($cookies['refreshToken']);
            if (is_null($refrech)) return $this->respondWithError(219);

            UserRefreshToken::query()
                ->where([['id', '=', $refrech['id']]])
                ->delete();

            if (!CheckTokenExpiration::action($this->container->get('jwt-secret'), $refrech['refresh_token'])
                || $refrech['fingerprint'] != $data['fingerprint']) {
                return $this->respondWithError(217);
            }

            $user = User::findOne($refrech['user_id']);
            $token = CreateToken::action($user['id'], $this->container->get('jwt-secret'), $user['role']);
            $refrech_token = CreateRefreshToken::action($user['id'], $this->container->get('jwt-secret'), $user['role'], $data['fingerprint']);
            $token_info = DecodeToken::action($this->container->get('jwt-secret'), $refrech_token);

            $expires = $token_info['exp'];
            $cookie[] = "refreshToken=$refrech_token; path=/api/auth; domain=.{$_ENV['HOST']}; maxAge=$expires; expires=$expires; HttpOnly";
            return $this->respondWithData(['access_token' => $token], 200, $cookie);

        }  catch (Throwable $e) {
            return $this->respondWithError(219);
        }
    }
}