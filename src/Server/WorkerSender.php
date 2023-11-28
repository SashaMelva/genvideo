<?php

namespace App\Server;

require __DIR__ . '/../../vendor/autoload.php';

use Exception;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$logger = new Logger('logger');
$logger->pushHandler(new RotatingFileHandler('var/log/test.log', 2));
$logger->pushHandler(new StreamHandler('php://stdout'));

try {
    // Соединение с RabbitMQ
    $connection = new AMQPStreamConnection('45.92.176.207', 5672, 'genvi', 'dr4_kdfuRh');
    $channel = $connection->channel();

    // Объявление очереди
//    $queueName = 'dev-queue';
//    $channel->queue_declare($queueName, false, true, false, false);
} catch (Exception $e) {
    $logger->info("Ошибка", ['error' => $e->getMessage()]);
    exit();
}

date_default_timezone_set('Europe/Moscow');
mb_internal_encoding("UTF-8");

// Сериализовать данные задачи
$data = json_encode(['test' => 'test']);
$msg = new AMQPMessage($data, array('delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT));

// Отправка сообщения в очередь
$channel->basic_publish($msg, '', $queueName);

// Закрытие соединений
try {
    $channel->close();
    $connection->close();
} catch (Exception $e) {
}