<?php

namespace App\Controller\Video;

use App\Controller\UserController;
use App\Helpers\CheckTokenExpiration;
use App\Helpers\GeneratorFiles;
use App\Models\ContentVideo;
use App\Models\ListVideo;
use App\Models\ListImage;
use App\Models\ListMusic;
use App\Models\TextVideo;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;

class CollectionDataVideo extends UserController
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function action(): ResponseInterface
    {
        $access_token = $this->request->getHeaderLine('token');
        $token = JWT::decode($access_token, new Key($this->container->get('jwt-secret'), 'HS256'));
        $data = json_decode($this->request->getBody()->getContents(), true);

        if (CheckTokenExpiration::action($this->container->get('jwt-secret'), $access_token)) {

            try {

                $dataFileText = (new GeneratorFiles())->generatorTextForTitre($data['text']);

                $text = TextVideo::addText(
                    $data['project_id'],
                    $data['text'],
                    $dataFileText['name'],
                    $dataFileText['path'],
                );

                $content = ContentVideo::addContent(
                    $data['name'],
                    $token->user_id,
                    null,
                    null,
                    $data['project_id'],
                    $data['type_background'],
                    $data['voice_id'],
                    $data['format'],
                    $data['color_background'],
                    1,
                    $text['id']
                );

                if (!empty($data['musics_ids'])) {
                    foreach ($data['musics_ids'] as $musicId) {
                        ListMusic::addMusic($musicId, $content->id);
                    }
                }

                if (!empty($data['images_ids'])) {
                    foreach ($data['images_ids'] as $imageId) {
                        ListImage::addImage($imageId, $content->id);
                    }
                }

                if (!empty($data['videos_ids'])) {
                    foreach ($data['videos_ids'] as $videoId) {
                        ListVideo::addVideo($videoId, $content->id);
                    }
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