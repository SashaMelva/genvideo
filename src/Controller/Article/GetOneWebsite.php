<?php

namespace App\Controller\Article;

use App\Controller\UserController;
use App\Helpers\CheckTokenExpiration;
use App\Models\Website;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class GetOneWebsite extends UserController
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function action(): ResponseInterface
    {
        $access_token = $this->request->getHeaderLine('Token');
        $websiteId = $this->request->getAttribute('id');

        if (CheckTokenExpiration::action($this->container->get('jwt-secret'), $access_token)) {

            try {

                $website = Website::findOne((int)$websiteId);
                return $this->respondWithData($website);

            } catch (Throwable $e) {
                return $this->respondWithError($e->getCode(), $e->getMessage());
            }
        } else {
            return $this->respondWithError(215);
        }
    }
}