<?php

namespace App\Console;

use App\Models\ContentVideo;
use App\Models\GPTChatCabinet;
use App\Models\GPTChatRequests;
use App\Models\ListCabinetGPTForProxy;
use App\Models\ListRequestGPTCabinet;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Database\Capsule\Manager as DB;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GeneratorChatGPTText extends Command
{
    private Logger $log;
    private bool $status_log;

    protected function configure(): void
    {
        $log = new Logger('info');
        $log->pushHandler(new RotatingFileHandler('../var/log/generator-gpt-text.log', 2, Logger::INFO));
        $log->pushHandler(new StreamHandler('php://stdout'));

        $this->log = $log;
        $this->status_log = true;

        parent::configure();

        $this
            ->setName('generator-gpt-text')
            ->setDescription('generator-gpt-text');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        date_default_timezone_set('Europe/Moscow');
        mb_internal_encoding("UTF-8");

        $cmd = '/usr/bin/supervisorctl stop generator-gpt-text';

        if ($this->status_log) {
            $this->log->info('Начало ' . date('Y-m-s H:i:s'));
        }

        $requestsLists = DB::table('list_GPT_chat_request')->where([['status_working', '=', 1]])->get()->toArray();

        if ($this->status_log) {
            $this->log->info('Получили запросы на генерацию');
        }

        if (empty($requestsLists)) {
            $this->log->info('Нет запросов на генерацию');
            exec($cmd);
            return 0;
        }

        $requestList = $requestsLists[0];

        try {

            if ($this->status_log) {
                $this->log->info('Запрос взят на отправку: ' . $requestList->id);
            }
            ListRequestGPTCabinet::changeStatus($requestList->id, 2);

            $request = GPTChatRequests::findOne($requestList->id_request);
          //  ContentVideo::changeStatus($request['content_id'], 7);

            $cabinet = GPTChatCabinet::findOne($requestList->id_cabinet);
            var_dump($cabinet);
            $proxy = ListCabinetGPTForProxy::findProxyByCabinetId($cabinet->id);
            var_dump($proxy);
            $response = $this->response($proxy['ip_address'], $proxy['port'], $proxy['user_name'], $proxy['password'], $cabinet->api_key, $request->text_request);

            if ($response['status'] == 'ok') {
                GPTChatCabinet::changeStatusCabinet($cabinet->id, true);
                ListRequestGPTCabinet::changeStatus($requestList->id, 4);
                GPTChatRequests::changeStatusAndContent($requestList->id_request, 4, $response['response']);
              //  ContentVideo::changeStatus($request->content_id, 6);
            } else {
                ListRequestGPTCabinet::changeStatus($requestList->id, 3);
                GPTChatRequests::changeStatusAndError($requestList->id_request, 3, $response['response']);
            }

        } catch (Exception $e) {
            $this->log->error($e->getMessage());
            $this->log->info('Контент опять поставлен в очередь на получении текста');
           // ContentVideo::changeStatus($request->content_id, 6);
        }

        if ($this->status_log) {
            $this->log->info('Выполнено ' . date('Y-m-s H:i:s'));
        }

        exec($cmd);
        return 0;
    }

    private function response($proxyIP, $proxyPort, $proxyUsername, $proxyPassword, $apiKey, $quire): array
    {
        $url = 'https://api.openai.com/v1/chat/completions';
        $headers = [
            "Authorization: Bearer " . $apiKey,
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

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            return ['status' => 'error', 'response' => curl_error($ch)];
        } else {
            return ['status' => 'ok', 'response' => $response];
        }
    }
}