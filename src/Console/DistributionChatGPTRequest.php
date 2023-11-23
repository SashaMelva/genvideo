<?php

namespace App\Console;

use App\Models\Article;
use App\Models\ContentVideo;
use App\Models\GPTChatCabinet;
use App\Models\GPTChatRequests;
use App\Models\ListRequestGPTCabinet;
use Exception;
use Illuminate\Database\Capsule\Manager as DB;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DistributionChatGPTRequest extends Command
{
    private Logger $log;
    private bool $status_log;

    protected function configure(): void
    {
        $log = new Logger('info');
        $log->pushHandler(new RotatingFileHandler('../var/log/division-gpt-request.log', 2, Logger::INFO));
        $log->pushHandler(new StreamHandler('php://stdout'));

        $this->log = $log;
        $this->status_log = true;

        parent::configure();

        $this
            ->setName('division-gpt-request')
            ->setDescription('division-gpt-request');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        date_default_timezone_set('Europe/Moscow');
        mb_internal_encoding("UTF-8");

        $cmd = '/usr/bin/supervisorctl stop division-gpt-request';

        if ($this->status_log) {
            $this->log->info('Начало ' . date('Y-m-s H:i:s'));
        }

        $requests = DB::table('GPT_chat_requests')->where([['status_working', '=', 5]])->get()->toArray();

        if (empty($requests)) {
            $this->log->info('Нет запросов на распределение');
            exec($cmd);
            return 0;
        }

        $request = $requests[0];
        try {

            if ($this->status_log) {
                $this->log->info('Контент взят на генерацию текста: ' . $request->id);
            }

            GPTChatRequests::changeStatus($request->id, 6);

            if (!empty($request->text_request)) {
                $cabinet = GPTChatCabinet::findAllFreeCabinetAndWork();

                if (!empty($cabinet)) {

                    $cabinet = $cabinet[0];
                    if ($this->status_log) {
                        $this->log->info('Найдены свободные кабинеты для отправки запроса');
                    }

                    GPTChatCabinet::changeStatusCabinet($cabinet['id'], false);
                    ListRequestGPTCabinet::addNewList($request->id, $cabinet['id'], 1);
                    GPTChatRequests::changeStatus($request->id, 1);

                    if ($this->status_log) {
                        $this->log->info('Задача добавлена');
                    }

                } else {

                    if ($this->status_log) {
                        $this->log->info('Нет свободных кабинетов для отправки запроса, запрос поставлен обратно в очередь');
                    }

                    GPTChatRequests::changeStatus($request->id, 5);
                    exec($cmd);
                    return 0;
                }

            } else {
                if ($this->status_log) {
                    $this->log->info('Не найден запрос на генерацию контента: ' . json_encode($requests));
                }
                GPTChatRequests::changeStatus($request->id, 3);

                if (!is_null($request->content_id)) {
                    ContentVideo::changeStatus($request->content_id, 8);
                } else {
                    Article::changeStatus($request->article_id, 7);
                }

                exec($cmd);
                return 0;
            }

        } catch (Exception $e) {
            $this->log->error($e->getMessage());
            $this->log->info('Контент опять поставлен в очередь на получении текста: ' . $request);

            if (!is_null($request->content_id)) {
                ContentVideo::changeStatus($request->content_id, 6);
            } else {
                Article::changeStatus($request->article_id, 2);
            }
        }

        if ($this->status_log) {
            $this->log->info('Выполнено ' . date('Y-m-s H:i:s'));
        }

        exec($cmd);
        return 0;
    }
}