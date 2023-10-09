<?php

namespace App\Controller\Video;

use App\Controller\UserController;
use App\Helpers\GeneratorFiles;
use App\Helpers\Speechkit;
use App\Models\ColorBackground;
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
            $resultName = '';

            $video = ContentVideo::findAllDataByID($videoId);
            $images = ListImage::findAllByContentId($video['content_id']);

            $logo = [];
            $slides = [];

            foreach ($images as $image) {
                if ($image['type'] == 'logo') {
                    $logo[] = $image['file_name'];
                }

                if ($image['type'] == 'slide') {
                    $slides[] = $image['file_name'];
                }
            }

            $sound = ListMusic::findAllByContentId($video['content_id']);
            $video['video'] = ListVideo::findAllByContentId($video['content_id']);

            $videoBackground = [];
            $videoStart = [];
            $videoEnd = [];

            foreach ($video['video'] as $additionalVideo) {
                if ($additionalVideo['type'] == 'content') {
                    $videoBackground[] = $additionalVideo['file_name'];
                }

                if ($additionalVideo['type'] == 'start') {
                    $videoStart[] = $additionalVideo['file_name'];
                }

                if ($additionalVideo['type'] == 'end') {
                    $videoEnd[] = $additionalVideo['file_name'];
                }
            }

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


//            if ($video['type_background'] == 'slide_show') {
//                $slideshow = $generatorFiles->generatorSladeShow($slides, $sound[0]['file_name'], $timeVoice);
//
//                var_dump($slideshow);
//                if (!$slideshow['status']) {
//                    return $this->respondWithError(400, 'Ошибка генерации слайдшоу');
//                }
//
//                $resultName = $slideshow['fileName'];
//            }

            $timeVoice = '54.64';


//            if ($video['type_background'] == 'video') {
//
//                if (!empty($videoBackground)) {
//                    $backgroundVideo = $generatorFiles->generatorBackgroundVideoAndMusic($videoBackground[0], $sound[0]['file_name'], $timeVoice);
//
//                    var_dump($backgroundVideo);
//                    if (!$backgroundVideo['status']) {
//                        return $this->respondWithError(400, 'Ошибка генерации фонового видео');
//                    }
//
//                    $resultName = $backgroundVideo['fileName'];
//                } else {
//                    return $this->respondWithError(400, 'Видео не загружено');
//                }
//            }
//
//            if (!is_null($video['color_background_id'])) {
//                $colorBackground = ColorBackground::findById((int)$video['color_background_id']);
//                $background = $generatorFiles->generatorBackground($colorBackground['file_name'], $resultName);
//
//                var_dump($background['status']);
//                if (!$background['status']) {
//                    return $this->respondWithError(400, 'Ошибка наложения фона видео');
//                }
//
//                $resultName = $background['fileName'];
//            }


//
//            if (!empty($logo)) {
//                $logoForVideo = $generatorFiles->generatorLogo($logo[0], $resultName);
//
//                var_dump($logoForVideo);
//                if (!$logoForVideo) {
//                    return $this->respondWithError(400, 'Ошибка прикрепления логотипа');
//                }
//            }

            $resultName = '7';

            if (!empty($videoEnd) || !empty($videoStart)) {
                $backgroundVideo = $generatorFiles->mergeVideo($resultName, $videoStart[0] ?? null, $videoEnd[0] ?? null);

                var_dump($backgroundVideo);
                if (!$backgroundVideo['status']) {
                    return $this->respondWithError(400, 'Ошибка склеивания видео');
                }

                $resultName = $backgroundVideo['fileName'];
            }


//            if ($textData['status']) {
//                $titers = $generatorFiles->generatorText();
//
//                if (!$titers) {
//                    return $this->respondWithError(400, 'Ошибка наложения субтитров');
//                }
//            }

        } catch (Exception $e) {
            return $this->respondWithError($e->getCode(), $e->getMessage());
        }

//        $dataFileText = (new GeneratorFiles())->generatorTextForTitre($data['text']);

    }

}