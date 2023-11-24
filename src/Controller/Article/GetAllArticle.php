<?php

namespace App\Controller\Article;

use App\Controller\UserController;
use App\Helpers\CheckTokenExpiration;
use App\Models\Article;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class GetAllArticle extends UserController
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function action(): ResponseInterface
    {
        $access_token = $this->request->getHeaderLine('Token');
        $projectId = $this->request->getAttribute('id');

        if (CheckTokenExpiration::action($this->container->get('jwt-secret'), $access_token)) {

            try {

                $article = Article::findAll($projectId);
                return $this->respondWithData($article);

            } catch (Throwable $e) {
                return $this->respondWithError($e->getCode(), $e->getMessage());
            }
        } else {
            return $this->respondWithError(215);
        }
    }
}