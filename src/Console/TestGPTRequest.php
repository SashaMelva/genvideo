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

            $query = "Напиши текст на тему: Важность медитации для жизни человека";

            $client = new Client();
//            $response = $client->post('http://45.92.176.207:4749/api/main',
//                [
//                    'headers' => [
//                        'Content-Type' => 'application/json'
//                    ],
//                    'json' => [
//                        'title' => $query
//                    ]
//                ]);
//
//            $responseData = json_decode($response->getBody()->getContents(), true);

//            print_r($responseData);
//            file_put_contents(DIRECTORY_RESULT_CONTENT . $query . '.txt',$responseData);



//            $url = "https://api.openai.com/v1/usage?date=2023-11-03";

            $url = "https://api.openai.com/v1/usage?date=2023-11-03";

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                "Authorization: Bearer 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"
            ));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($ch);
            curl_close($ch);

            var_dump($response);
            if ($response) {
                $data = json_decode($response, true);
                if (isset($data['usage']) && isset($data['usage']['total_tokens'])) {
                    $remaining_tokens = $data['usage']['total_tokens'];
                    echo "Оставшиеся токены: " . $remaining_tokens;
                } else {
                    echo "Не удалось получить информацию о балансе токенов.";
                }
            } else {
                echo "Ошибка при выполнении запроса к API OpenAI.";
            }

//            $headers = [
//                "Authorization: Bearer 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",
//                "Content-Type: application/json"
//            ];
//
//// URL сайта, на который отправляем запрос
//            $url = 'https://api.openai.com/v1/dashboard/billing/subscription';
//
//// Прокси сервер и порт
//            $proxyIP = '194.5.148.174';
//            $proxyPort = 59101;
//
//// Логин и пароль для авторизации на прокси
//            $proxyUsername = '2499995';
//            $proxyPassword = 'eXtzmMmro3';
//
//// Инициализация curl
//            $ch = curl_init();
//
//// Установка URL и других параметров
//            curl_setopt($ch, CURLOPT_URL, $url);
//            curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5_HOSTNAME);
//            curl_setopt($ch, CURLOPT_PROXY, $proxyIP);
//            curl_setopt($ch, CURLOPT_PROXYPORT, $proxyPort);
//            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyUsername . ':' . $proxyPassword);
//            // curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP); // Измените тип прокси, если это не HTTP
//
//// Установка метода и данных для отправки
//            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// Получение ответа
            $response = curl_exec($ch);
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