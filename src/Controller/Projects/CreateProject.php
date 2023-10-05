<?php

namespace App\Controller\Projects;

use App\Controller\UserController;
use App\Helpers\CheckTokenExpiration;
use App\Models\ListProject;
use App\Models\Project;
use App\Models\User;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;

class CreateProject extends UserController
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

                $project = new Project();
                $project->setAttribute('name', $data['name']);
                $project->setAttribute('creator_id', $token->user_id);
                $project->setAttribute('created_at', new \DateTimeImmutable());
                $project->setAttribute('updated_at', new \DateTimeImmutable());

                if ($project->validate()) {
                    $project->save();

                    ListProject::addProject($token->user_id, $project->id);
                } else {
                    return $this->respondWithError(400, $project->getValidationErrors());
                }

                return $this->respondWithData('Success');
            } catch (Exception $e) {
                return $this->respondWithError($e->getCode(), $e->getMessage());
            }
        } else {
            return $this->respondWithError(215);
        }
    }
}