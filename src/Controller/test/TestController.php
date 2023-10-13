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
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;

class TestController extends UserController
{

    private Client $client;

    public function action(): ResponseInterface
    {
        $output = null;
        $retval=[];
        exec('yc iam create-token', $retval);
        print_r($retval);

//        $client = new Google_Client();
//
//        $client->setDeveloperKey(API_KEY_YOUTUBE);
//
//        $service = new Google_Service_YouTube($client);
//
//        $video = new Google_Service_YouTube_Video();
//
//        $videoSnippet = new Google_Service_YouTube_VideoSnippet();
//        $videoSnippet->setDescription('Описание');
//        $videoSnippet->setTitle('Нахвание');
//        $video->setSnippet($videoSnippet);
//
//        $videoStatus = new Google_Service_YouTube_VideoStatus();
//        $videoStatus->setPrivacyStatus('public');
//        $video->setStatus($videoStatus);
//
//        try {
//            $response = $service->videos->insert(
//                'snippet,status',
//                $video,
//                array(
//                    'data' => file_get_contents(DIRECTORY_VIDEO . '65_text.mp4'),
//                    'mimeType' => 'video/*',
//                    'uploadType' => 'multipart'
//                )
//            );
//            echo "Video uploaded successfully. Video id is ". $response->id;
//
//        } catch(Exception $e) {
//               echo $e->getMessage();
//        }

//        $client = new Client();
//
//        $response = $this->client->post('https://www.googleapis.com/upload/youtube/v3/videos',
//            [
//                'headers' => [
//                    'Authorization' => 'Bearer ' . $token,
//                    'x-folder-id' => 'b1glckrv5eg7s4kkhtpn',
//                    'Content-Type' => 'application/x-www-form-urlencoded'
//                ],
//                'form_params' => [
//                    'text' => $text,
//                    'format' => $voiceSetting['format'],
//                    'lang' => $voiceSetting['lang'],
//                    'voice' => $voiceSetting['voice'],
//                    'emotion' => $voiceSetting['emotion'],
//                    'folderId' => 'b1glckrv5eg7s4kkhtpn'
//                ]
//            ]
//        );

//        $video_url = 'https://youtu.be/NWU7kjCFSlQ?si=Au81laP9OZU6li34';
//        // Преобразование ссылки в id YouTube
//        $pattern = '#^(?:https?://)?(?:www\.)?(?:youtu\.be/|youtube\.com(?:/embed/|/v/|/watch\?v=|/watch\?.+&v=))([\w-]{11})(?:.+)?$#x';
//        preg_match($pattern, $video_url, $matches);
//        $video_id = (isset($matches[1])) ? $matches[1] : false;
//
//        // Получение данных при помощи YouTube API
//        $api_key = "AIzaSyAwGUSLR-S7iSRFO6JDZwlEskC_5M6zeys";
//        $url = "https://www.googleapis.com/youtube/v3/videos?id=" . $video_id . "&key=" . $api_key . "&part=snippet,contentDetails,status";
//        $getData = json_decode(file_get_contents($url), true);
//
//        var_dump($getData);
    }
}

