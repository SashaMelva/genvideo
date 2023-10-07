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
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;

class GeneratorVideo extends UserController
{
    /**
     * @throws GuzzleException
     * @throws Exception
     */
    public function action(): ResponseInterface
    {
        $data = json_decode($this->request->getBody()->getContents(), true);
        $access_token = $this->request->getHeaderLine('token');

        $videoId = $this->request->getAttribute('id');

        try {


            $video = ContentVideo::findAllDataByID($videoId);

            $video['images'] = ListImage::findAllByContentId($video['content_id']);
            $video['sound'] = ListMusic::findAllByContentId($video['content_id']);
            $video['video'] = ListVideo::findAllByContentId($video['content_id']);

            $generatorFiles = new GeneratorFiles($video['content_id']);

            var_dump($video['status_voice']);
            if ($video['status_voice']) {

                $fileName = $video['content_id'] . $video['text_id'];
                $voiceSetting = [
                    'format' => 'mp3',
                    'lang' => $video['language'],
                    'voice' => $video['dictionary_voice_name'],
                    'emotion' => $video['ampula_voice'],
                ];

                $timeVoice = (new Speechkit())->generator($video['text'], $fileName, $voiceSetting);

                if ($timeVoice == 0) {
                    return $this->respondWithError(400, 'Ошибка получения аудио озвучки');
                } else {
                    TextVideo::updateFileVoice($video['text_id'], $fileName, RELATIVE_PATH_SPEECHKIT . $fileName . '.' . $voiceSetting['format'], true, $timeVoice);
                }
            }

            if (!$video['status_text']) {
                $textData = $generatorFiles->generatorTextForTitre($video['text']);
            }

            if ($video['type_background'] == 'slide') {

            }

            if ($video['type_background'] == 'video') {

            }
        } catch (Exception $e) {
            return $this->respondWithError($e->getCode(), $e->getMessage());
        }

//        $dataFileText = (new GeneratorFiles())->generatorTextForTitre($data['text']);

    }

}