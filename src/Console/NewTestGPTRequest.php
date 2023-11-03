<?php

namespace App\Console;

use App\Models\GPTChatRequests;
use Exception;
use GuzzleHttp\Client;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class NewTestGPTRequest extends Command
{
    private Logger $log;
    private bool $status_log;

    protected function configure(): void
    {
        $log = new Logger('info');
        $log->pushHandler(new RotatingFileHandler('../var/log/new-test-gpt.log', 2, Logger::INFO));
        $log->pushHandler(new StreamHandler('php://stdout'));

        $this->log = $log;
        $this->status_log = true;

        parent::configure();

        $this
            ->setName('new-test-gpt')
            ->setDescription('new-test-gpt');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        date_default_timezone_set('Europe/Moscow');
        mb_internal_encoding("UTF-8");

        $cmd = '/usr/bin/supervisorctl stop new-test-gpt';

        if ($this->status_log) {
            $this->log->info('Начало ' . date('Y-m-s H:i:s'));
        }

        try {

            $query = "Напиши текст на тему: Важность медитации для жизни человека";
            $headers = [
                "Authorization: Bearer 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",
                "Content-Type: application/json"
            ];
            $post_data = [
                "model" => "gpt-3.5-turbo",
                "messages" => [["role" => "user", "content" => "Напиши 100 заголовков для видео о медитации"]],
                "temperature" => 0.7
            ];

//            $post_data = '{
//                "model" => "gpt-3.5-turbo",
//                "messages" => [{
//                        "role" => "user",
//                        "content" => "Say this is a test!"
//                }],
//                "temperature" => 0.7
//            }';




// URL сайта, на который отправляем запрос
            $url = 'https://api.openai.com/v1/chat/completions';

// Прокси сервер и порт
            $proxyIP = '194.5.148.174';
            $proxyPort = 59101;

// Логин и пароль для авторизации на прокси
            $proxyUsername = '2499995';
            $proxyPassword = 'eXtzmMmro3';

// Инициализация curl
            $ch = curl_init();

// Установка URL и других параметров
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5_HOSTNAME);
            curl_setopt($ch, CURLOPT_PROXY, $proxyIP);
            curl_setopt($ch, CURLOPT_PROXYPORT, $proxyPort);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyUsername . ':' . $proxyPassword);
           // curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP); // Измените тип прокси, если это не HTTP

// Установка метода и данных для отправки
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));


// Получение ответа
            $response = curl_exec($ch);

// Проверка на наличие ошибок
            if (curl_errno($ch)) {
                echo 'Ошибка CURL: ' . curl_error($ch);
            } else {
                echo 'Ответ сервера: ' . $response;
            }
            GPTChatRequests::changeStatusAndContent(20, 4, $response);


// Закрытие сеанса CURL
            curl_close($ch);

//

//            var_dump($responseData['choices'][0]['message']['content']);
////            print_r($responseData);*/
//            file_put_contents(DIRECTORY_RESULT_CONTENT . 123 . '.txt', $responseData['choices'][0]['message']['content']);
//            file_put_contents(DIRECTORY_RESULT_CONTENT . 123 . 'response.txt', $responseData);


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