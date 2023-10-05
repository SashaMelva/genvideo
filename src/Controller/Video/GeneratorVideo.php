<?php

namespace App\Controller\Video;

use App\Controller\UserController;
use App\Models\ContentVideo;
use App\Models\ListVideo;
use App\Models\ListImage;
use App\Models\ListMusic;
use App\Models\TextVideo;
use Exception;
use Psr\Http\Message\ResponseInterface;

class GeneratorVideo extends UserController
{
    public function action(): ResponseInterface
    {
        $data = json_decode($this->request->getBody()->getContents(), true);
        $access_token = $this->request->getHeaderLine('token');
        $videoId = $this->request->getAttribute('id');


        var_dump(RELATIVE_PATH_IMG);
        var_dump(RELATIVE_PATH_MUSIC);
        var_dump(RELATIVE_PATH_SPEECHKIT);
        var_dump(RELATIVE_PATH_VIDEO);

        exit();
        $video = ContentVideo::findAllDataByID($videoId);

        $video['text'] = TextVideo::findAllByContentId($video['text_id']);
        $video['images'] = ListImage::findAllByContentId($video['id']);
        $video['sound'] = ListMusic::findAllByContentId($video['id']);
        $video['video'] = ListVideo::findAllByContentId($video['id']);



    }

}