<?php

namespace App\Console;

use App\Models\ContentVideo;
use GuzzleHttp\Promise\Promise;
use Illuminate\Database\Capsule\Manager as DB;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use React\ChildProcess\Process;
use React\EventLoop\Factory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestScript extends Command
{
    private Logger $log;
    private bool $status_log;

    protected function configure(): void
    {
        $log = new Logger('info');
        $log->pushHandler(new RotatingFileHandler('../var/log/test-new', 2, Logger::INFO));
        $log->pushHandler(new StreamHandler('php://stdout'));

        $this->log = $log;
        $this->status_log = true;

        parent::configure();

        $this
            ->setName('test-new')
            ->setDescription('test-new');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        date_default_timezone_set('Europe/Moscow');
        mb_internal_encoding("UTF-8");

        $cmd = '/usr/bin/supervisorctl stop test-new';

        if ($this->status_log) {
            $this->log->info('Начало ' . date('Y-m-s H:i:s'));
        }

        $contentIds = DB::table('content')->select('id')->where([['status_id', '=', 3]])->get()->toArray();
        $videoId = $contentIds[0]->id;
        ContentVideo::changeStatus($videoId, 20);

        if ($this->status_log) {
            $this->log->info('Задачи на генерацию: ' . json_encode($contentIds));
        }
        $this->log->info('Взяли задачу ' . $videoId);

        $loop = Factory::create();

        $this->log->info('Поменяли статус ' . $videoId);
        sleep(5);
        $this->log->info('Конец');

        exec($cmd);
        return 0;

// Функция для отправки запроса


// Массив URL-адресов для отправки запросов
        $urls = [
            'https://example.com/api/1'
        ];

        $promises = [];

// Создаем Promise-объекты для каждого URL-адреса
        foreach ($urls as $url) {
            $promise = $this->sendRequest($url);
            $promises[] = $promise;
        }

// Ждем выполнения всех Promise-объектов
        React\Promise\all($promises)->done(function ($results) {
            // Выводим результаты успешных запросов
            echo "Successful requests:\n";
            foreach ($results as $result) {
                echo "$result\n";
            }
        }, function ($error) {
            // Выводим ошибки запросов
            echo "Error in request:\n";
            echo "$error\n";
        });

        $loop->run();
    }

    function sendRequest($qwere, $loop)
    {
        // Создаем новый Promise
        $promise = new Promise(function ($resolve, $reject) use ($loop, $qwere) {
            $process = new Process('curl -X GET ' . $qwere);

            // Подписываемся на событие 'exit'
            $process->on('exit', function ($exitCode, $termSignal) use ($resolve, $reject, $qwere) {
                if ($exitCode === 0) {
                    // Возвращаем результат успешного выполнения запроса
                    $resolve("Success: $qwere");
                } else {
                    // Возвращаем ошибку выполнения запроса
                    $reject("Error: $qwere");
                }
            });

            $process->start($loop);
        });

        return $promise;
    }

}