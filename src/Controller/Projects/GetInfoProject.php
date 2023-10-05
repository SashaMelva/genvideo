<?php

namespace App\Controller\Projects;

use App\Controller\UserController;
use App\Helpers\CheckTokenExpiration;
use App\Models\ListProject;
use App\Models\Project;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class GetInfoProject extends UserController
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

                $projectId = $this->request->getAttribute('id');
                $project = Project::fullInfo((int)$projectId)[0];

                $users = ListProject::findAllUsersInfoByProjectId((int)$projectId);
                $project['users'] = $users ?? null;
                return $this->respondWithData($project);

            } catch (Throwable $e) {
                return $this->respondWithError($e->getCode(), $e->getMessage());
            }
        } else {
            return $this->respondWithError(215);
        }
    }
}