<?php

namespace App\Console;

use App\Models\Article;
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

class FormatTextArticleFromChatGpt  extends Command
{
    private Logger $log;
    private bool $status_log;

    protected function configure(): void
    {
        $log = new Logger('info');
        $log->pushHandler(new RotatingFileHandler('../var/log/format-text-article-gpt.log', 2, Logger::INFO));
        $log->pushHandler(new StreamHandler('php://stdout'));

        $this->log = $log;
        $this->status_log = true;

        parent::configure();

        $this
            ->setName('format-text-article-gpt')
            ->setDescription('format-text-article-gpt');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        date_default_timezone_set('Europe/Moscow');
        mb_internal_encoding("UTF-8");

        $cmd = '/usr/bin/supervisorctl stop format-text-article-gpt';

        if ($this->status_log) {
            $this->log->info('Начало ' . date('Y-m-s H:i:s'));
        }

        $articles = DB::table('articles')->select('id')->where([['status_id', '=', 4]])->get()->toArray();

        if ($this->status_log) {
            $this->log->info('Статья на форматирования текста: ' . json_encode($articles));
        }

        if (empty($articles)) {
            $this->log->info('Нет задач на генерацию текста');
            exec($cmd);
            return 0;
        }

        $articleId = $articles[0]->id;

        try {

            if ($this->status_log) {
                $this->log->info('Статья взят на генерацию текста: ' . $articleId);
            }

            Article::changeStatus($articleId, 5);

            $gptRequest = GPTChatRequests::findByContentId($articleId);

            if (!empty($gptRequest)) {

                $gptRequest = $gptRequest[0];

                if ($this->status_log) {
                    $this->log->info('Ответ запроса взят на обработку: ' . $gptRequest['id']);
                }

                $replaceString = str_replace("\n", ' ', $gptRequest["response"]);
                $replaceString = str_replace('\n', '\\\n', $replaceString);
                $replaceString = str_replace('\r', '\\\r', $replaceString);
                $replaceString = str_replace('\t', '\\\t', $replaceString);
                $resultTextArray = json_decode($replaceString, true);
                $resultText = $resultTextArray['choices'][0]['message']['content'];

                if (empty($resultText)) {
                    $this->log->error('Ответ запроса путой, контент поставлен в очередь на получение резкльтата запроса: ' . $articleId);
                    Article::changeStatus($articleId, 1);
                    exec($cmd);
                    return 0;
                }

                $this->log->info('Соранение результата');
                TextVideo::updatedContentData($gptRequest['text_id'], $resultText);
                Article::changeStatus($articleId, 6);

            } else {

                if ($this->status_log) {
                    $this->log->info('Не найден запрос на генерацию контента: ' . $articleId);
                    Article::changeStatus($articleId, 9);
                    exec($cmd);
                    return 0;
                }
            }

        } catch (Exception $e) {
            $this->log->error($e->getMessage());
            $this->log->info('Ошибка форматирования текста: ' . $articleId);
            Article::changeStatus($articleId, 9);
        }

        if ($this->status_log) {
            $this->log->info('Выполнено ' . date('Y-m-s H:i:s'));
        }

        exec($cmd);
        return 0;
    }
}