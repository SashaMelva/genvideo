<?php

namespace App\Controller\Projects;

use App\Controller\UserController;
use App\Helpers\CheckTokenExpiration;
use App\Models\ContentVideo;
use App\Models\ListProject;
use App\Models\Project;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class GetAllProject  extends UserController
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

                $projects = ListProject::findAllProjectInfoByUserId($token->user_id);

                foreach ($projects as $project) {
                    $project['count_content'] = ContentVideo::countContent($project['project_id']);
                }

                return $this->respondWithData($projects);

            } catch (Throwable $e) {
                return $this->respondWithError($e->getCode(), $e->getMessage());
            }
        } else {
            return $this->respondWithError(215);
        }
    }
}