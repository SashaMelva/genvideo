<?php

namespace App\Controller\Article;

use App\Controller\UserController;
use App\Helpers\CheckTokenExpiration;
use App\Models\User;
use App\Models\Website;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;

class AddNewWebsite extends UserController
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function action(): ResponseInterface
    {

        $data = json_decode($this->request->getBody()->getContents(), true);
        $access_token = $this->request->getHeaderLine('token');
        $token = JWT::decode($access_token, new Key($this->container->get('jwt-secret'), 'HS256'));

        if (CheckTokenExpiration::action($this->container->get('jwt-secret'), $access_token)) {

            if (!User::accessCheck($token->user_id)) return $this->respondWithError(215);

            try {
                $project = new Website();
                $project->setAttribute('domen', $data['domen']);
                $project->setAttribute('user_name', $data['user_name']);
                $project->setAttribute('password_app', $data['password_app']);
                $project->setAttribute('name', $data['name']);
                $project->setAttribute('description', $data['description']);
                $project->setAttribute('user_id', $token->user_id);

                $project->save();

                return $this->respondWithData('Success');

            } catch (Exception $e) {
                return $this->respondWithError($e->getCode(), $e->getMessage());
            }
        } else {
            return $this->respondWithError(215);
        }
    }
}