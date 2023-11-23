<?php

namespace App\Console;

use App\Models\Article;
use Exception;
use Illuminate\Database\Capsule\Manager as DB;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SendingArticleWordpress extends Command
{
    private Logger $log;
    private bool $status_log;

    protected function configure(): void
    {
        $log = new Logger('info');
        $log->pushHandler(new RotatingFileHandler('../var/log/generator-image.log', 2, Logger::INFO));
        $log->pushHandler(new StreamHandler('php://stdout'));

        $this->log = $log;
        $this->status_log = true;

        parent::configure();

        $this
            ->setName('generator-image')
            ->setDescription('generator-image');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        date_default_timezone_set('Europe/Moscow');
        mb_internal_encoding("UTF-8");

        $cmd = '/usr/bin/supervisorctl stop generator-image';

        if ($this->status_log) {
            $this->log->info('Начало ' . date('Y-m-s H:i:s'));
        }

        $articlesIds = DB::table('articles')->select('id')->where([['status_id', '=', 6]])->get()->toArray();

        if ($this->status_log) {
            $this->log->info('Контенты на форматирования текста: ' . json_encode($articlesIds));
        }

        if (empty($articlesIds)) {
            $this->log->info('Нет задач на генерацию текста');
            exec($cmd);
            return 0;
        }

        $articleId = $articlesIds[0]->id;

        try {

            if ($this->status_log) {
                $this->log->info('Контент взят на генерацию текста: ' . $articleId);
            }

            Article::changeStatus($articleId, 7);

            $articleData = Article::findAllById($articleId);

        } catch (Exception $e) {
            $this->log->error($e->getMessage());
            $this->log->info('Ошибка форматирования текста: ' . $articleId);
            Article::changeStatus($articleId, 9);
        }

        Article::changeStatus($articleId, 8);
        $this->log->info('Выполнено ' . date('Y-m-s H:i:s'));

        exec($cmd);
        return 0;
    }
}