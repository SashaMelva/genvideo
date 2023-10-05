<?php

namespace App\Controller\Users;

use App\Controller\UserController;
use App\Helpers\CheckTokenExpiration;
use App\Models\ListProject;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class GetUserInfo extends UserController
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
                $token = JWT::decode($access_token, new Key($this->container->get('jwt-secret'), 'HS256'));

                $user = User::query()
                    ->select(['id', 'name', 'email', 'phone', 'role'])
                    ->findOrFail($token->user_id)->toArray();

                $project = ListProject::findAllProjectInfoByUserId($token->user_id);
                $user['projects'] = $project ?? null;
                return $this->respondWithData($user);

            } catch (Throwable $e) {
                return $this->respondWithError($e->getCode(), $e->getMessage());
            }
        } else {
            return $this->respondWithError(215);
        }
    }
}