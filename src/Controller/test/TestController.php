<?php

namespace App\Controller\test;

use App\Controller\UserController;
use App\Helpers\CheckTokenExpiration;
use App\Helpers\GeneratorFiles;
use App\Helpers\Speechkit;
use App\Helpers\UploadFile;
use App\Models\ContentVideo;
use App\Models\ListImage;
use App\Models\ListMusic;
use App\Models\ListVideo;
use App\Models\TextVideo;
use App\Models\User;
use Exception;
use getID3;
use GuzzleHttp\Client;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;

class TestController extends UserController
{

    private Client $client;

    public function action(): ResponseInterface
    {
        $getID3 = new getID3;
        $file = $getID3->analyze($filePath);
        $seconds = $file['playtime_seconds'];

        var_dump($file);
        var_dump($seconds);

//        $videoId = 7;
//        $video = ContentVideo::findAllDataByID($videoId);
//        $images = ListImage::findAllByContentId($video['content_id']);
//
//        $logo = [];
//        $slides = [];
//
//        foreach ($images as $image) {
//            if ($image['type'] == 'logo') {
//                $logo[] = $image['file_name'];
//            }
//
//            if ($image['type'] == 'slide') {
//                $slides[] = $image['file_name'];
//            }
//        }
//
//        $sound = ListMusic::findAllByContentId($video['content_id']);
//        $video['video'] = ListVideo::findAllByContentId($video['content_id']);
//
//        $videoBackground = [];
//        $videoStart = [];
//        $videoEnd = [];
//
//        foreach ($video['video'] as $additionalVideo) {
//            if ($additionalVideo['type'] == 'content') {
//                $videoBackground[] = $additionalVideo['file_name'];
//            }
//
//            if ($additionalVideo['type'] == 'start') {
//                $videoStart[] = $additionalVideo['file_name'];
//            }
//
//            if ($additionalVideo['type'] == 'end') {
//                $videoEnd[] = $additionalVideo['file_name'];
//            }
//        }
//
//        $generatorFiles = new GeneratorFiles($video['content_id']);
//
//        $timeVoice = '54.648010530935';
//
//        if ($video['type_background'] == 'slide_show') {
//            $slideshow = $generatorFiles->generatorSladeShow($slides, $sound[0]['file_name'], $timeVoice);
//
//            var_dump($slideshow);
//            if (!$slideshow['status']) {
//                return $this->respondWithError(400, 'Ошибка генерации слайдшоу');
//            }
//
//            $resultName = $slideshow['fileName'];
//        }
//
//        return $this->respondWithData($resultName);
    }
}

