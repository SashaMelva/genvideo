<?php

namespace App\Controller\test;

use App\Controller\UserController;
use App\Helpers\CheckTokenExpiration;
use App\Helpers\UploadFile;
use App\Models\User;
use Exception;
use GuzzleHttp\Client;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;

class TestController extends UserController
{

    private Client $client;

    public function action(): ResponseInterface
    {
        $row['text'] = 'Мем — единица значимой для культуры информации.з предложил идею о том';
        $this->client = new Client();

        $response = $this->client->post('https://tts.api.cloud.yandex.net/speech/v1/tts:synthesize',
            [
                'headers' => [
                    'Authorization' => 'Bearer t1.9euelZqLjs-OjIvMk46JmpCPmseei-3rnpWamJiTmpbNnImPnZPPjMbHnJDl8_dhZgRX-e9uZSJf_d3z9yEVAlf5725lIl_9zef1656VmpCQmomZzcrMypuZmZmKm5SU7_zF656VmpCQmomZzcrMypuZmZmKm5SU.jyNwOhBbREoIrIBwyS8xDo6cnKK40GDLm11tv9bieKXsMYjcllOV_8CC7VxQu4aYIT8VskxuxsPy959G41r5Dw',
                    'x-folder-id' => 'b1glckrv5eg7s4kkhtpn',
                    'Content-Type' => 'application/x-www-form-urlencoded'
                ],
                'form_params' => [
                    'text' => $row['text'],
                    'format' => 'mp3',
                    'lang' => 'ru-RU',
                    'voice' => 'jane',
                    'emotion' => 'good',
                    'folderId' => 'b1glckrv5eg7s4kkhtpn'
                ]
            ]);

        $length = file_put_contents(DIRECTORY_MUSIC . 'music.mp3', $response->getBody()->getContents());
//        $body = $response->getBody();
//        var_dump($body);
//        $data = $body->getContents();
//        var_dump($data);
//        $filename = UploadFile::action(DIRECTORY_IMG, $data , 8);
        var_dump($filename);
        exit();

//        return $this->respondWithError(215);
    }

}

