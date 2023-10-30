<?php

namespace App\Controller\Projects;

use App\Controller\UserController;
use App\Helpers\CheckTokenExpiration;
use App\Models\ImageVideo;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class GetAllImageProject extends UserController
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

                $images = ImageVideo::findAllByProjectId((int)$projectId);
                foreach ($images as $key => $image) {
                    if ($image['type'] == 'slide') {
                        $images[$key]['content_image'] = base64_encode(file_get_contents(DIRECTORY_IMG . $image['file_name']));
                    }

                    if ($image['type'] == 'logo') {
                        $images[$key]['content_image'] = base64_encode(file_get_contents(DIRECTORY_LOGO_IMG . $image['file_name']));
                    }
                }

                return $this->respondWithData($images);

            } catch (Throwable $e) {
                return $this->respondWithError($e->getCode(), $e->getMessage());
            }
        } else {
            return $this->respondWithError(215);
        }
    }
}