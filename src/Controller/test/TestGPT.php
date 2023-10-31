<?php

namespace App\Controller\test;

use App\Controller\UserController;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;

class TestGPT extends UserController
{

    private Client $client;


    /**
     * @throws GuzzleException
     */
    public function action(): ResponseInterface
    {
        $query = "Напиши 100 заголовков под ключевые слова: отношения мать отец";

        $client = new Client();

//        $response = $client->post('http://127.0.0.1:5000/api/main',
//            [
//                'headers' => [
//                    'Content-Type' => 'application/json'
//                ],
//                'json' => [
//                    'title' => $query
//                ]
//            ]);
//
//        $responseData = json_decode($response->getBody()->getContents(), true);
//
//        file_put_contents(DIRECTORY_RESULT_CONTENT . $query . '.txt',$responseData);
//
//        return $this->respondWithData('Ok');

        $response = $client->get('http://127.0.0.1:5000/api/main');


        return $this->respondWithData($response);
    }
}