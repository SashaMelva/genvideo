<?php

namespace App\Console;

use App\Models\ContentVideo;
use App\Models\GPTChatRequests;
use App\Models\TextVideo;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Database\Capsule\Manager as DB;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestGPTRequest  extends Command
{
    private Logger $log;
    private bool $status_log;

    protected function configure(): void
    {
        $log = new Logger('info');
        $log->pushHandler(new RotatingFileHandler('../var/log/test-gpt.log', 2, Logger::INFO));
        $log->pushHandler(new StreamHandler('php://stdout'));

        $this->log = $log;
        $this->status_log = true;

        parent::configure();

        $this
            ->setName('test-gpt')
            ->setDescription('test-gpt');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        date_default_timezone_set('Europe/Moscow');
        mb_internal_encoding("UTF-8");

        $cmd = '/usr/bin/supervisorctl stop test-gpt';

        if ($this->status_log) {
            $this->log->info('Начало ' . date('Y-m-s H:i:s'));
        }

        try {

            $query = "напиши текст мощной медитации на тему: Медитация на свободу от контроля и привязанности к тревоге";

            $client = new Client();
            $response = $client->post('http://45.92.176.207:4749/api/main',
                [
                    'headers' => [
                        'Content-Type' => 'application/json'
                    ],
                    'json' => [
                        'title' => $query
                    ]
                ]);

            $responseData = json_decode($response->getBody()->getContents(), true);

            print_r($responseData);
            file_put_contents(DIRECTORY_RESULT_CONTENT . $query . '.txt',$responseData);


        } catch (Exception $e) {
            $this->log->error($e->getMessage());
        }

        if ($this->status_log) {
            $this->log->info('Выполнено ' . date('Y-m-s H:i:s'));
        }

        exec($cmd);
        return 0;
    }
}