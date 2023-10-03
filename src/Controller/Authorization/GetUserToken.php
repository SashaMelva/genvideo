<?php

namespace App\Controller\Authorization;

use App\Controller\UserController;
use App\Helpers\CheckTokenExpiration;
use App\Helpers\CreateToken;
use App\Models\User;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class GetUserToken  extends UserController
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function action(): ResponseInterface
    {
        $access_token = $this->request->getHeaderLine('Token');

        if (CheckTokenExpiration::action($this->container->get('jwt-secret'), $access_token)) {

            try {
                if (is_null($this->request->getAttribute('id')))
                    return $this->respondWithError(221);

                $user = User::findByUserId($this->request->getAttribute('id'));

                if (is_null($user)) return $this->respondWithError(221);

                $token = CreateToken::action($user['id'], $this->container->get('jwt-secret'), $user['role']);
                return $this->respondWithData(['access_token' => $token]);

            } catch (Throwable $e) {
                return $this->respondWithError($e->getCode(), $e->getMessage());
            }
        }
        else {
            return $this->respondWithError(215);
        }
    }
}