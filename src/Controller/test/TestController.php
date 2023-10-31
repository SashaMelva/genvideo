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
        $textData = TextVideo::findById(176)[0];
        var_dump($textData['id']);
        //return $this->respondWithData($textData[0]['time']);
    }
}

