<?php

namespace App\Console;

use App\Models\ContentVideo;
use Illuminate\Database\Capsule\Manager as DB;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
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

        $contentIds = DB::table('content')->select('id')->where([['status_id', '=', 20]])->get()->toArray();

        if ($this->status_log) {
            $this->log->info('Задачи на генерацию: ' . json_encode($contentIds));
        }
        $videoId = $contentIds[0]->id;
        $this->log->info('Взяли задачу ' . $videoId);
        ContentVideo::changeStatus($videoId, 3);

        $this->log->info('Поменяли статус ' . $videoId);
        sleep(5);
        $this->log->info('Конец');

        exec($cmd);
        return 0;
    }

}