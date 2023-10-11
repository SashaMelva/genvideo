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
        $data = [];

        $videoName = '7_result1';
        $resultName = 7 . '_mobile';


        $ffmpeg = 'ffmpeg -i ' . DIRECTORY_VIDEO . $videoName . '.mp4' . ' -vf "scale=100:-1,setdar=9/16" ' . DIRECTORY_VIDEO . $resultName . '1.mp4';
        $errors = shell_exec($ffmpeg . ' -hide_banner -loglevel error 2>&1');

        var_dump($ffmpeg);
        if (is_null($errors)) {
            $data['status'] = true;
        }

        var_dump($data);

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

