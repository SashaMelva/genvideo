<?php

namespace App\Controller\Video;

use App\Controller\UserController;
use App\Helpers\CreateToken;
use App\Models\ContentVideo;
use App\Models\TokenYoutube;
use App\Models\User;
use Exception;
use Google\Service\YouTube\Video;
use Google_Client;
use Google_Service_YouTube;
use Google_Service_YouTube_Video;
use Google_Service_YouTube_VideoSnippet;
use Google_Service_YouTube_VideoStatus;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Header\Headers;
use Throwable;

class SendVideo extends UserController
{
    public function action(): ResponseInterface
    {

//        $token = CreateToken::action($user['id'], $this->container->get('jwt-secret'), $user['role'], true);
        $data = json_decode($this->request->getBody()->getContents(), true);

//        try {

        $userId = $data['user_id'];
        $dataVideo = ContentVideo::findByID($data['content_id']);
        $tokenCheck = TokenYoutube::checkToken($userId);
        $client = new Google_Client();


        if ($tokenCheck) {
            $allDataToken = TokenYoutube::getTokenByUserId($userId);

            $access_token = json_decode($allDataToken['access_token'], true);

            $client->setAccessToken($access_token['access_token']);
            $service = new Google_Service_YouTube($client);
            $video = new Google_Service_YouTube_Video();
            $videoSnippet = new Google_Service_YouTube_VideoSnippet();


            $videoSnippet->setDescription('qqqqq');
            $videoSnippet->setTitle($dataVideo['content_name']);
            $videoSnippet->setDefaultLanguage('ru');
            $video->setSnippet($videoSnippet);

            $videoStatus = new Google_Service_YouTube_VideoStatus();
            $videoStatus->setPrivacyStatus('public');
            $video->setStatus($videoStatus);


//            try {
                $response = $service->videos->insert(
                    'snippet,status',
                    $video,
                    array(
                        'data' => file_get_contents(DIRECTORY_VIDEO . $dataVideo['file_name']),
                        'mimeType' => 'video/*',
                        'uploadType' => 'multipart'
                    )
                );

                echo "Video uploaded successfully. Video id is " . $response->id;

//            } catch (Exception $e) {
//                if (401 == $e->getCode()) {
//                    $client = new Client(['base_uri' => 'https://accounts.google.com']);
//
//                    $response = $client->request('POST', '/o/oauth2/token', [
//                        'form_params' => [
//                            "grant_type" => "refresh_token",
//                            "refresh_token" => $allDataToken['refresh_token'],
//                            "client_id" => '574380164386-5pkdkk4v6ikbb5mani0phg477jqc39f0.apps.googleusercontent.com',
//                            "client_secret" => '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',
//                        ],
//                    ]);
//
//                    $data = (array)json_decode($response->getBody());
//                    TokenYoutube::updateToken($userId, json_encode($data), $allDataToken['refresh_token']);
//
//                    var_dump(3);
//                } else {
//                    echo $e->getMessage();
//                }
//            }
        } else {

            $redirect_uri = 'http://localhost:8080/api/token/' . $userId;
            return $this->response->withHeader('Location', filter_var($redirect_uri, FILTER_SANITIZE_URL));
        }

        return $this->respondWithData('Success');

//        } catch (Throwable $e) {
//            return $this->respondWithError($e->getCode(), $e->getMessage());
//        }
    }
}