<?php

namespace App\Console;

use Exception;
use Illuminate\Database\Capsule\Manager as DB;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GetYandexAimToken extends Command
{
    private Logger $log;
    private bool $status_log;

    protected function configure(): void
    {
        $log = new Logger('info');
        $log->pushHandler(new RotatingFileHandler('../var/log/get-token.log', 2, Logger::INFO));
        $log->pushHandler(new StreamHandler('php://stdout'));

        $this->log = $log;
        $this->status_log = true;

        parent::configure();

        $this
            ->setName('get-token')
            ->setDescription('get-token');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        date_default_timezone_set('Europe/Moscow');
        $cmd = '/usr/bin/supervisorctl stop domain-for-get-course';

        if ($this->status_log) {
            $this->log->info('Начало ' . date('Y-m-s H:i:s'));
        }

        $dir = '/var/www/genvideo/api/var/token/token.txt';
        try {
            exec('yc iam create-token > ' . $dir);
            DB::table('token_yandex')->where([['id', '=', 1]])->update(['token' => file_get_contents($dir)]);
            unlink($dir);

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