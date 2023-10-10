<?php

namespace App\Controller\Video;

use App\Controller\UserController;
use App\Helpers\CheckTokenExpiration;
use App\Models\AdditionalVideo;
use App\Models\ColorBackground;
use App\Models\ImageVideo;
use App\Models\MusicVideo;
use App\Models\Voice;
use Psr\Http\Message\ResponseInterface;

class GetCollectionDataSettingVideo extends UserController
{
    public function action(): ResponseInterface
    {
        $access_token = $this->request->getHeaderLine('Token');

        if (CheckTokenExpiration::action($this->container->get('jwt-secret'), $access_token)) {

            $data = json_decode($this->request->getBody()->getContents(), true);
            $voices = Voice::getAllData();

            foreach ($voices as $key => $voice) {
                $voices[$key]['amplua'] = !is_null($voice['amplua']) ? explode(',', $voice['amplua']) : null;
            }

            $images = ImageVideo::findAllByProjectId($data['project_id']);
            $video = AdditionalVideo::findAllByProjectId($data['project_id']);
            $musics = MusicVideo::findAllByProjectId($data['project_id']);
            $logo = [];

            foreach ($images as $key => $img) {
                if ($img['type'] == 'logo') {
                    $logo = $img;
                    unset($images[$key]);
                }
            }

            $result = [
                'images' => $images,
                'logo' => $logo,
                'video' => $video,
                'music' => $musics,
                'voice' => $voices,
                'color_background' => ColorBackground::findAll(),
            ];

            return $this->respondWithData($result);

        } else {
            return $this->respondWithError(215);
        }
    }

}