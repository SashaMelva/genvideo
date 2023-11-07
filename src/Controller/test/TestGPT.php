<?php

namespace App\Controller\test;

use App\Controller\UserController;
use App\Models\ContentVideo;
use App\Models\GPTChatCabinet;
use App\Models\GPTChatRequests;
use App\Models\ListCabinetGPTForProxy;
use App\Models\ListRequestGPTCabinet;
use App\Models\TextVideo;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Database\Capsule\Manager as DB;
use Psr\Http\Message\ResponseInterface;

class TestGPT extends UserController
{

    private Client $client;


    /**
     * @throws GuzzleException
     */
    public function action(): ResponseInterface
    {

        $requestsLists = DB::table('list_GPT_chat_request')->where([['status_working', '=', 1]])->get()->toArray();

        $requestList = $requestsLists[0];
        ListRequestGPTCabinet::changeStatus($requestList->id, 2);

        $request = GPTChatRequests::findOne($requestList->id_request);
        //  ContentVideo::changeStatus($request['content_id'], 7);

        $cabinet = GPTChatCabinet::findOne($requestList->id_cabinet);
        $proxy = ListCabinetGPTForProxy::findProxyByCabinetId($cabinet->id);
        $response = $this->response($proxy['ip_address'], $proxy['port'], $proxy['user_name'], $proxy['password'], $cabinet->api_key, $request->text_request);



        var_dump($response);
//        $contentIds = DB::table('content')->select('id')->where([['status_id', '=', 8]])->get()->toArray();

//        $contentId = 187;
//
//        try {
//            ContentVideo::changeStatus($contentId, 9);
//
//            $gptRequest = GPTChatRequests::findByContentId($contentId);
//            if (!empty($gptRequest)) {
//
//
//
//                $gptRequest = $gptRequest[0];
//                $replaceString = str_replace("\n", ' ', $gptRequest["response"]);
//                $replaceString = str_replace('\n', '\\\n', $replaceString);
//                $replaceString = str_replace('\r', '\\\r', $replaceString);
//                $replaceString = str_replace('\t', '\\\t', $replaceString);
//                $resultTextArray = json_decode($replaceString, true);
//                $resultText = $resultTextArray['choices'][0]['message']['content'];
//                var_dump($resultText['choices'][0]['message']['content']);
//                exit();
//
//                if (empty($resultText)) {
//                    ContentVideo::changeStatus($contentId, 5);
//                }
//
//                TextVideo::updatedContentData($gptRequest['text_id'], $resultText);
//                ContentVideo::changeStatus($contentId, 1);
//                //ContentVideo::changeStatus($contentId, 10);
//
//            } else {
//
//
//                ContentVideo::changeStatus($contentId, 5);
//
//
//                ContentVideo::changeStatus($contentId, 8);
//            }
//
//        } catch (Exception $e) {
//            ContentVideo::changeStatus($contentId, 5);
//        }
//
//        return $this->respondWithData('Ok');

//        $response = $client->get('http://127.0.0.1:5000/api/main');
//
//
//        return $this->respondWithData($response);
    }

    private function response($proxyIP, $proxyPort, $proxyUsername, $proxyPassword, $apiKey, $quire): array
    {
        $url = 'https://api.openai.com/v1/chat/completions';
        $headers = [
            "Authorization: Bearer 1wewaw" . $apiKey,
            "Content-Type: application/json"
        ];
        $post_data = [
            "model" => "gpt-3.5-turbo",
            "messages" => [["role" => "user", "content" => "" . $quire]],
            "temperature" => 0.7
        ];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5_HOSTNAME);
        curl_setopt($ch, CURLOPT_PROXY, $proxyIP);
        curl_setopt($ch, CURLOPT_PROXYPORT, $proxyPort);
        curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyUsername . ':' . $proxyPassword);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        var_dump($response);

        if (curl_errno($ch)) {
            return ['status' => 'errorConnection', 'response' => curl_error($ch)];
        } elseif(isset(json_decode($response, true)['error']) ) {
            return ['status' => 'error', 'response' => $response];
        } else {
            return ['status' => 'ok', 'response' => $response];
        }
    }
}