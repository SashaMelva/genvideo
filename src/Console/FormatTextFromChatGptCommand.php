<?php

namespace App\Console;

use App\Models\ContentVideo;
use App\Models\GPTChatRequests;
use App\Models\TextVideo;
use Exception;
use Illuminate\Database\Capsule\Manager as DB;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FormatTextFromChatGptCommand  extends Command
{
    private Logger $log;
    private bool $status_log;

    protected function configure(): void
    {
        $log = new Logger('info');
        $log->pushHandler(new RotatingFileHandler('../var/log/format-text-gpt.log', 2, Logger::INFO));
        $log->pushHandler(new StreamHandler('php://stdout'));

        $this->log = $log;
        $this->status_log = true;

        parent::configure();

        $this
            ->setName('format-text-gpt')
            ->setDescription('format-text-gpt');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        date_default_timezone_set('Europe/Moscow');
        mb_internal_encoding("UTF-8");

        $cmd = '/usr/bin/supervisorctl stop format-text-gpt';

        if ($this->status_log) {
            $this->log->info('Начало ' . date('Y-m-s H:i:s'));
        }

        $contentIds = DB::table('content')->select('id')->where([['status_id', '=', 8]])->get()->toArray();

        if ($this->status_log) {
            $this->log->info('Контенты на форматирования текста: ' . json_encode($contentIds));
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

            ContentVideo::changeStatus($contentId, 9);

            $gptRequest = GPTChatRequests::findByContentId($contentId);

            if (!empty($gptRequest)) {

                $gptRequest = $gptRequest[0];

                if ($this->status_log) {
                    $this->log->info('Ответ запроса взят на обработку: ' . $gptRequest['id']);
                }

                $resultText = $gptRequest["response"];

                if (empty($resultText)) {
                    $this->log->error('Ответ запроса путой, контент поставлен в очередь на получение резкльтата запроса: ' . $contentId);
                    ContentVideo::changeStatus($contentId, 5);
                    exec($cmd);
                    return 0;
                }

                $this->log->info('Начало форматирования запроса');

                $this->log->info('Соранение результата');
                TextVideo::updatedContentData($gptRequest['text_id'], $resultText);

            } else {

                if ($this->status_log) {
                    $this->log->info('Не найден запрос на генерацию контента: ' . $contentId);
                    ContentVideo::changeStatus($contentId, 5);
                    exec($cmd);
                    return 0;
                }

                ContentVideo::changeStatus($contentId, 8);
            }

        } catch (Exception $e) {
            $this->log->error($e->getMessage());
            $this->log->info('Ошибка форматирования текста: ' . $contentId);
            ContentVideo::changeStatus($contentId, 5);
        }

        if ($this->status_log) {
            $this->log->info('Выполнено ' . date('Y-m-s H:i:s'));
        }

        exec($cmd);
        return 0;
    }
}