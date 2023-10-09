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

//            if ($video['status_voice']) {
//
//                $fileName = $video['content_id'] . $video['text_id'];
//                $voiceSetting = [
//                    'format' => 'mp3',
//                    'lang' => $video['language'],
//                    'voice' => $video['dictionary_voice_name'],
//                    'emotion' => $video['ampula_voice'],
//                ];
//
//                $timeVoice = (new Speechkit())->generator($video['text'], $fileName, $voiceSetting);
//
//                if ($timeVoice == 0) {
//                    return $this->respondWithError(400, 'Ошибка генерации аудио озвучки');
//                } else {
//                    TextVideo::updateFileVoice($video['text_id'], $fileName, RELATIVE_PATH_SPEECHKIT . $fileName . '.' . $voiceSetting['format'], true, $timeVoice);
//                }
//            }

//            var_dump($video['status_text']);
//            var_dump($video['status_text'] == 'false');

           // if ($video['status_text'] == 'false' || $video['status_text'] == 'создано') {
//                TextVideo::changeTextStatus($video['text_id'], 'в обработке');
//                $textData = $generatorFiles->generatorTextForTitre($video['text'], $video['text_id']);
//
//                if ($textData['status']) {
//                    TextVideo::changeTextStatus($video['text_id'], 'обработано');
//                } else {
//                    $this->respondWithError(400, 'Ошибка генерации субтитров');
//                    TextVideo::changeTextStatus($video['text_id'], 'ошибка');
//                }
           // }

            var_dump($video['type_background']);
            if ($video['type_background'] == 'slide_show') {

            }

            if ($video['type_background'] == 'video') {

            }

            if (!is_null($video['color_background_id'])) {

            }

            if ($video['images'] == 'video') {

            }
        } catch (Exception $e) {
            return $this->respondWithError($e->getCode(), $e->getMessage());
        }

//        $dataFileText = (new GeneratorFiles())->generatorTextForTitre($data['text']);

    }

}