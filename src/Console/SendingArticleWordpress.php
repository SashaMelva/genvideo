<?php

namespace App\Console;

use App\Models\Article;
use Exception;
use GuzzleHttp\Client;
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
        $log->pushHandler(new RotatingFileHandler('../var/log/send-article.log', 2, Logger::INFO));
        $log->pushHandler(new StreamHandler('php://stdout'));

        $this->log = $log;
        $this->status_log = true;

        parent::configure();

        $this
            ->setName('send-article')
            ->setDescription('send-article');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        date_default_timezone_set('Europe/Moscow');
        mb_internal_encoding("UTF-8");

        $cmd = '/usr/bin/supervisorctl stop send-article';

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
            $article = Article::findAllById($articleId);

            $url = '/wp-json/wp/v2/posts?title=' . $article['name'] . '&status=draft&content=' . $article['text'];

            if (!is_null($article['rubric'])) {
                $url .= '&categories=' . $article['rubric'];
            }

            if (!is_null($article['marking'])) {
                $url .= '&tags=' . $article['marking'];
            }

            $client = new Client([
                'base_uri' => 'https://' . $article['domen'],
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode($article['user_name'] . ':' . $article['password_app'])
                ]
            ]);

            $res = $client->post($url);

            if ($res->getStatusCode() !== 200) {
                $this->log->info('Ошибка отправки запроса: ' . $articleId);
                Article::changeStatus($articleId, 9);
            }

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