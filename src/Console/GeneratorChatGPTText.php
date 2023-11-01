<?php

namespace App\Console;

use App\Models\ContentVideo;
use App\Models\GPTChatRequests;
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

        $contentIds = DB::table('content')->select('id')->where([['status_id', '=', 6]])->get()->toArray();

        if ($this->status_log) {
            $this->log->info('Задачи на генерацию текста: ' . json_encode($contentIds));
        }

        if (empty($contentIds)) {
            $this->log->info('Нет задач на генерацию текста');
            exec($cmd);
            return 0;
        }

        $contentId = $contentIds[0]->id;

        try {

            if ($this->status_log) {
                $this->log->info('Контент взят на генерацию текста: ' . $contentId);
            }

            ContentVideo::changeStatus($contentId, 7);

            $gptRequest = GPTChatRequests::findByContentId($contentId);

            if (!empty($gptRequest)) {

                $gptRequest = $gptRequest[0];

                if ($this->status_log) {
                    $this->log->info('Запрос взят на генерацию: ' . $gptRequest['id']);
                }

                if (empty($gptRequest["text_request"])) {
                    $this->log->error('Запрос для генерации текста пустой: ' . $gptRequest['text_request']);
                    ContentVideo::changeStatus($contentId, 5);
                    exec($cmd);
                    return 0;
                }

                $client = new Client();

                $response = $client->post('http://45.92.176.207:4749/api/main',
                    [
                        'headers' => [
                            'Content-Type' => 'application/json'
                        ],
                        'json' => [
                            'title' => $gptRequest['text_request']
                        ]
                    ]);

                $responseData = json_decode($response->getBody()->getContents(), true);

                if (is_null($responseData)) {
                    ContentVideo::changeStatus($contentId, 6);
                    exec($cmd);
                    return 0;
                }

                if ($responseData['status'] == 'Ok') {
                    $text = $responseData['response'];

                    if (empty($text)) {

                        $this->log->error('Результат запроса пустой текст контент поставлен в очередь'. $contentId);
                        GPTChatRequests::changeStatusError($gptRequest['id'], 3, 'Ответ пустой');
                        ContentVideo::changeStatus($contentId, 6);
                        exec($cmd);
                        return 0;

                    } else {

                        $this->log->error('Успех. Получили результат запроса '. $gptRequest['id']);
                        GPTChatRequests::changeStatusAndContent($gptRequest['id'], 2, $text);
                        ContentVideo::changeStatus($contentId, 8);
                        exec($cmd);
                        return 0;
                    }

                } else {
                    $this->log->info('Ошика при получении текста: ' . $responseData['response']);
                    GPTChatRequests::changeStatusError($gptRequest['id'], 3, $responseData['response']);
                    ContentVideo::changeStatus($contentId, 6);
                    exec($cmd);
                    return 0;
                }

            } else {
                if ($this->status_log) {
                    $this->log->info('Не найден запрос на генерацию контента: ' . json_encode($contentIds));
                    ContentVideo::changeStatus($contentId, 8);
                    exec($cmd);
                    return 0;
                }
                ContentVideo::changeStatus($contentId, 8);
            }

        } catch (Exception $e) {
            $this->log->error($e->getMessage());
            $this->log->info('Контент опять поставлен в очередь на получении текста: ' . $contentId);
            ContentVideo::changeStatus($contentId, 6);
        }

        if ($this->status_log) {
            $this->log->info('Выполнено ' . date('Y-m-s H:i:s'));
        }

        exec($cmd);
        return 0;
    }
}