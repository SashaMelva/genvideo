<?php

namespace App\Controller\Video;

use App\Controller\UserController;
use App\Helpers\CheckTokenExpiration;
use App\Models\ContentVideo;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class LoadPreviewVideo extends UserController
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function action(): ResponseInterface
    {
        $access_token = $this->request->getHeaderLine('Token');
        $contentId = $this->request->getAttribute('id');

        ini_set('memory_limit', '-1');

        if (CheckTokenExpiration::action($this->container->get('jwt-secret'), $access_token)) {

            try {

                $content = ContentVideo::findByID((int)$contentId);

                if (!is_null($content['preview_name'])) {
                    $preview = [
                        'id_content' => $content['content_id'],
                        'content_image' => base64_encode(file_get_contents(DIRECTORY_PREVIEW . $content['preview_file_name'])),
                        'name' => $content['preview_file_name'],
                        'file_name' => $content['preview_name'],
                        'text' => $content['preview_text'],
                    ];

                    return $this->respondWithData($preview);
                }

                return $this->respondWithError(400, 'Превью для видео не найдено');

            } catch (Throwable $e) {
                return $this->respondWithError($e->getCode(), $e->getMessage());
            }
        } else {
            return $this->respondWithError(215);
        }
    }
}