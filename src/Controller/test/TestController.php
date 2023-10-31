<?php

namespace App\Controller\test;

use App\Controller\UserController;
use App\Helpers\CheckTokenExpiration;
use App\Helpers\GeneratorFiles;
use App\Helpers\Speechkit;
use App\Helpers\UploadFile;
use App\Models\ColorBackground;
use App\Models\ContentVideo;
use App\Models\ListImage;
use App\Models\ListMusic;
use App\Models\ListVideo;
use App\Models\TextVideo;
use App\Models\User;
use Exception;
use getID3;
use Google_Client;
use Google_Service_YouTube;
use Google_Service_YouTube_Video;
use Google_Service_YouTube_VideoSnippet;
use Google_Service_YouTube_VideoStatus;
use GuzzleHttp\Client;
use Illuminate\Database\Capsule\Manager as DB;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;

class TestController extends UserController
{

    private Client $client;


    public function action(): ResponseInterface
    {

        var_dump($this->formatMilliseconds('215.552036923077' * 1000));
        //return $this->respondWithData($textData[0]['time']);
    }
    private function formatMilliseconds($milliseconds): string
    {
        $seconds = floor($milliseconds / 1000);
        $minutes = floor($seconds / 60);
        $hours = floor($minutes / 60);
        $milliseconds = $milliseconds % 1000;
        $seconds = $seconds % 60;
        $minutes = $minutes % 60;

        $format = '%u:%02u:%02u.%03u';

        return sprintf($format, $hours, $minutes, $seconds, $milliseconds);
    }
}

