<?php

namespace App\Console;

use App\Helpers\GeneratorFiles;
use App\Helpers\Speechkit;
use App\Models\ColorBackground;
use App\Models\ContentVideo;
use App\Models\GPTChatRequests;
use App\Models\ListImage;
use App\Models\ListMusic;
use App\Models\ListVideo;
use App\Models\TextVideo;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
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

        $contentId = 147;//$contentIds[0]->id;

        try {

            if ($this->status_log) {
                $this->log->info('Контент взят на генерацию: ' . $contentId);
            }

            ContentVideo::changeStatus($contentId, 7);

            $gptRequest = GPTChatRequests::findByContentId($contentId);

            if (!empty($gptRequest)) {

                $gptRequest = $gptRequest[0];

                if ($this->status_log) {
                    $this->log->info('Запрос взят на генерацию: ' . $gptRequest['id']);
                }

                if (empty($gptRequest["text_request"])) {
                    $this->log->info('Запрос для генерации текста пустой: ' . $gptRequest['id']);
                    exec($cmd);
                    return 0;
                }

                $flagResponse = true;
                $client = new Client();

                $response = $client->post('http://127.0.0.1:8888/api/main',
                    [
                        'headers' => [
                            'Content-Type' => 'application/json'
                        ],
                        'json' => [
                            'title' => $gptRequest['text_request']
                        ]
                    ]);

                var_dump($response->getBody()->getContents());
                $responseData = json_encode($response->getBody()->getContents(), JSON_UNESCAPED_UNICODE);

                var_dump($responseData);

                exit();
                if ($responseData['status'] == 'Ok') {
                    $text = $responseData['response'];

                    if (empty($text)) {
                        $flagResponse = false;
                    } else {

                        TextVideo::updatedContentData($gptRequest['text_id'], $text);
                        GPTChatRequests::changeStatusAndContent($gptRequest['id'], $text);
                    }

                } else {
                    $flagResponse = false;
                }

            } else {

                if ($this->status_log) {
                    $this->log->info('Не найден запрос на генерацию контента: ' . json_encode($contentIds));
                }

                ContentVideo::changeStatus($contentId, 8);
            }


        } catch (Exception $e) {
            $this->log->error($e->getMessage());
            ContentVideo::changeStatus($contentId, 5);
        } catch (GuzzleException $e) {
            $this->log->error($e->getMessage());
            ContentVideo::changeStatus($contentId, 5);
        }

        if ($this->status_log) {
            $this->log->info('Выполнено ' . date('Y-m-s H:i:s'));
        }

        exec($cmd);
        return 0;
    }
}