<?php

namespace App\Controller\Files;

use App\Controller\UserController;
use App\Helpers\CheckTokenExpiration;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;

class GetTypeImage extends UserController
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function action(): ResponseInterface
    {
        $access_token = $this->request->getHeaderLine('Token');

        if (CheckTokenExpiration::action($this->container->get('jwt-secret'), $access_token)) {

            return $this->respondWithData(['slide', 'logo', 'banner']);

        } else {
            return $this->respondWithError(215);
        }
    }
}