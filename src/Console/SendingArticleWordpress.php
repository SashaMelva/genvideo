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
            $this->log->info('Статьи на отправку: ' . json_encode($articlesIds));
        }

        if (empty($articlesIds)) {
            $this->log->info('Нет задач на отправку');
            exec($cmd);
            return 0;
        }

        $articleId = $articlesIds[0]->id;

        try {

            if ($this->status_log) {
                $this->log->info('Статья взята на отправку в вордпресс: ' . $articleId);
            }

            Article::changeStatus($articleId, 7);
            $article = Article::findAllById($articleId);

            $text = str_replace('\n', "\n",  $article['text']);
            var_dump($text);
            $requestFlag = $this->senTextJson($article, $text);


            if (!$requestFlag) {
                Article::changeStatus($articleId, 9);
            }

        } catch (Exception $e) {
            $this->log->error($e->getMessage());
            $this->log->info('Ошибка отправки статьи: ' . $articleId);
            Article::changeStatus($articleId, 9);
        }

        Article::changeStatus($articleId, 8);
        $this->log->info('Выполнено ' . date('Y-m-s H:i:s'));

        exec($cmd);
        return 0;
    }

    private function senTextJson(array $article, string $text): bool
    {
        $url = 'https://' . $article['domen'] . '/wp-json/wp/v2/posts';

        $client = new Client();

        $postData = [
            'title' => $article['name'],
            'content' => $text,
        ];

        if (!is_null($article['rubric'])) {
            $postData[] = ['categories'=>$article['rubric']];
        }

        if (!is_null($article['marking'])) {
            $postData[] = ['tags' => $article['marking']];
        }

        if (!is_null($article['date_publish'])) {
            $postData[] = [
                'date' => date('Y-m-dH:i:s', $article['date_publish']),
                'status' => 'publish',
            ];
        } else {
            $postData[] = [
                'status' => 'draft',
            ];
        }

        var_dump( json_encode($postData));
        $res = $client->post($url, [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($article['user_name'] . ':' . $article['password_app']),
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode($postData),
        ]);

        if ($res->getStatusCode() != 201 && $res->getStatusCode() != 200) {
            $this->log->info('Ошибка отправки запроса');
            return false;
        }

        return true;
    }
}