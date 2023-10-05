<?php

namespace App\Controller\Authorization;

use App\Controller\UserController;
use App\Helpers\CheckTokenExpiration;
use App\Models\UserRefreshToken;
use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;

class LogoutUser extends UserController
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function action(): ResponseInterface
    {
        $cookies = $this->request->getCookieParams();
        $access_token = $this->request->getHeaderLine('Token');

        if (CheckTokenExpiration::action($this->container->get('jwt-secret'), $access_token)) {

            if (!array_key_exists('refreshToken', $cookies))
                return $this->respondWithError(200);

            try {

                $refrech = UserRefreshToken::findByToken($cookies['refreshToken']);
                if (is_null($refrech)) return $this->respondWithError(200);

                UserRefreshToken::query()
                    ->where([['id', '=', $refrech['id']]])
                    ->delete();

                $refrech_token = $cookies['refreshToken'];
                $expires = time() - 3600;
                $cookie[] = "refreshToken=$refrech_token; path=/api/auth; domain=.{$_ENV['HOST']}; maxAge=-3600; expires=$expires; HttpOnly";
                return $this->respondWithData('Success', 200, $cookie);

            } catch (Exception $e) {
                return $this->respondWithError($e->getCode(), $e->getMessage());
            }

        } else {
            return $this->respondWithError(215);
        }
    }
}