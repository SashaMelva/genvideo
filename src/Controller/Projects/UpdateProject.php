<?php

namespace App\Controller\Projects;

use App\Controller\UserController;
use App\Helpers\CheckTokenExpiration;
use App\Models\Project;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;

class UpdateProject extends UserController
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

            $userId = $token->user_id;

            if (!Project::accessCheckCreator($data['project_id'], $userId)) return $this->respondWithError(215);

            try {
                Project::updateName($data['project_id'], $data['name']);
                return $this->respondWithData('Success');

            } catch (Exception $e) {
                return $this->respondWithError($e->getCode(), $e->getMessage());
            }
        } else {
            return $this->respondWithError(215);
        }
    }
}