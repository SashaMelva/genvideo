<?php

namespace App\Controller\Article;

use App\Controller\UserController;
use App\Helpers\CheckTokenExpiration;
use App\Models\Website;
use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;

class UpdateWebsite extends UserController
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function action(): ResponseInterface
    {
        $data = json_decode($this->request->getBody()->getContents(), true);
        $access_token = $this->request->getHeaderLine('token');

        if (CheckTokenExpiration::action($this->container->get('jwt-secret'), $access_token)) {

            try {

                Website::updateName($data['website_id'], $data);
                return $this->respondWithData('Success');

            } catch (Exception $e) {
                return $this->respondWithError($e->getCode(), $e->getMessage());
            }
        } else {
            return $this->respondWithError(215);
        }
    }
}