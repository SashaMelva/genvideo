<?php

namespace App\Controller\Video;

use App\Controller\UserController;
use App\Helpers\GeneratorFiles;
use App\Helpers\Speechkit;
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
        $video = ContentVideo::findAllDataByID($videoId);

        $video['images'] = ListImage::findAllByContentId($video['content_id']);
        $video['sound'] = ListMusic::findAllByContentId($video['content_id']);
        $video['video'] = ListVideo::findAllByContentId($video['content_id']);

        $generatorFiles = new GeneratorFiles($video['content_id']);

        if (!$video['status_voice']) {
            $fileNameSpeechkit = $video['content_id'] . $video['text_id'];
            $timeVoice = (new Speechkit())->generator($video['text'], $fileNameSpeechkit);

            if ($timeVoice == 0) {
                #TODO
            } else {
                TextVideo::updateFileVoice($video['text_id'], $fileNameSpeechkit, RELATIVE_PATH_SPEECHKIT . $fileNameSpeechkit, true, $timeVoice);
            }
        }

        if (!$video['status_text']) {
            $textData = $generatorFiles->generatorTextForTitre($video['text']);
        }

        if ($video['type_background'] == 'slide') {

        }

        if ($video['type_background'] == 'video') {

        }


//        $dataFileText = (new GeneratorFiles())->generatorTextForTitre($data['text']);

    }

}