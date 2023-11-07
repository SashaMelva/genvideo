<?php

namespace App\Console;

use App\Models\ContentVideo;
use App\Models\GPTChatCabinet;
use App\Models\GPTChatRequests;
use App\Models\ListCabinetGPTForProxy;
use App\Models\ListRequestGPTCabinet;
use Exception;
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
            ContentVideo::changeStatus($request['content_id'], 7);

            $cabinet = GPTChatCabinet::findOne($requestList->id_cabinet);
            $proxy = ListCabinetGPTForProxy::findProxyByCabinetId($cabinet->id);
            $response = $this->response($proxy['ip_address'], $proxy['port'], $proxy['user_name'], $proxy['password'], $cabinet->api_key, $request->text_request);
            $this->log->info('Получили ответ со статусом: ' . $response['status']);

            if ($response['status'] == 'ok') {

                GPTChatCabinet::changeStatusCabinet($cabinet->id, true);
                ListRequestGPTCabinet::changeStatus($requestList->id, 4);
                GPTChatRequests::changeStatusAndContent($requestList->id_request, 4, $response['response']);
                ContentVideo::changeStatus($request->content_id, 8);

            } elseif ($response['status'] == 'errorConnection') {

                $this->log->info('Фиксируем ошибку в кабинете  ' . $cabinet->id . ' и отправляем запрос на получение нового кабинета ' . $requestList->id_request);
                GPTChatCabinet::changeStatusWorkCabinet($cabinet->id, 5, $response['response']);
                ListRequestGPTCabinet::changeStatus($requestList->id, 3);
                GPTChatRequests::changeStatus($requestList->id_request, 5);

            } else {

                $this->log->info('Фиксируем ошибку в кабинете  ' . $cabinet->id . ' и отправляем запрос на получение нового кабинета ' . $requestList->id_request);
                $textError = $response['response'];

                if (stripos($textError, 'Incorrect API key provided') !== false) {
                    GPTChatCabinet::changeStatusWorkCabinet($cabinet->id, 4, $response['response']);
                } elseif (stripos($textError, 'Rate limit reached for requests') || stripos($textError, 'Too Many Requests') !== false) {
                    GPTChatCabinet::changeStatusWorkCabinet($cabinet->id, 3, $response['response']);
                } else {
                    GPTChatCabinet::changeStatusWorkCabinet($cabinet->id, 2, $response['response']);
                }

                ListRequestGPTCabinet::changeStatus($requestList->id, 3);
                GPTChatRequests::changeStatus($requestList->id_request, 5);
            }

        } catch (Exception $e) {
            $this->log->error($e->getMessage());
            $this->log->info('Запрос поставлен в очередь на получение нового кабинета');
            ListRequestGPTCabinet::changeStatus($requestList->id, 3);
            GPTChatRequests::changeStatus($requestList->id_request, 5);
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
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            return ['status' => 'errorConnection', 'response' => curl_error($ch)];
        } elseif(isset(json_decode($response, true)['error']) ) {
            return ['status' => 'error', 'response' => $response];
        } else {
            return ['status' => 'ok', 'response' => $response];
        }
    }
}