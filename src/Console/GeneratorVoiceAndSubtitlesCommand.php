<?php

namespace App\Console;

use App\Helpers\Speechkit;
use App\Models\ContentVideo;
use App\Models\TextVideo;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Database\Capsule\Manager as DB;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GeneratorVoiceAndSubtitlesCommand extends Command
{
    private Logger $log;
    private bool $status_log;

    protected function configure(): void
    {
        $log = new Logger('info');
        $log->pushHandler(new RotatingFileHandler('../var/log/generator-voice-subtitles.log', 2, Logger::INFO));
        $log->pushHandler(new StreamHandler('php://stdout'));

        $this->log = $log;
        $this->status_log = true;

        parent::configure();

        $this
            ->setName('generator-voice-subtitles')
            ->setDescription('generator-voice-subtitles');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        date_default_timezone_set('Europe/Moscow');
        mb_internal_encoding("UTF-8");

        $cmd = '/usr/bin/supervisorctl stop generator-voice-subtitles';

        if ($this->status_log) {
            $this->log->info('Начало ' . date('Y-m-s H:i:s'));
        }

        $contentIds = DB::table('content')->select('id')->where([['status_id', '=', 9]])->get()->toArray();

        if ($this->status_log) {
            $this->log->info('Контенты на форматирования текста: ' . json_encode($contentIds));
        }

        if (empty($contentIds)) {
            $this->log->info('Нет задач на генерацию текста');
            exec($cmd);
            return 0;
        }

        $contentId = $contentIds[0]->id;

        try {

            if ($this->status_log) {
                $this->log->info('Контент взят на генерацию текста: ' . $contentId);
            }

            ContentVideo::changeStatus($contentId, 10);
            $video = ContentVideo::findAllDataByID($contentId);

            if ($video['status_voice'] == 0) {

                $fileNameVoice = $contentId . '_' . $video['text_id'];
                $voiceSetting = [
                    'format' => 'mp3',
                    'lang' => $video['language'],
                    'voice' => $video['dictionary_voice_name'],
                    'emotion' => $video['ampula_voice'],
                    'delay_between_offers_ms' => $video['delay_between_offers'],
                    'voice_speed' => is_null($video['voice_speed']) ? '1.0' : $video['voice_speed'],
                ];

                if ($video['subtitles']) {

                    $voiceData = (new Speechkit())->generatorWithSubtitles($video['text'], $fileNameVoice, $voiceSetting);

                    if ($voiceData['status']) {

                        TextVideo::updateFileTextAndStatus($video['text_id'], $fileNameVoice, RELATIVE_PATH_TEXT . $fileNameVoice, '1');
                        TextVideo::updateFileVoice($video['text_id'], $fileNameVoice, RELATIVE_PATH_SPEECHKIT . $fileNameVoice . '.' . $voiceSetting['format'], '1', $voiceData['time']);
                        ContentVideo::changeStatus($contentId, 5);
                        $this->log->info('Успех генерации субтитров, id текста ' . $video['text_id']);
                        $this->log->info('Успех генерации аудио озвучки, id текста ' . $video['text_id']);

                    } else {
                        if (isset($voiceData['command'])) {
                            $this->log->error($voiceData['command'] . $video['text_id']);
                        }

                        TextVideo::changeVoiceStatus($video['text_id'], '3');
                        TextVideo::changeTextStatus($video['text_id'], '3');
                        ContentVideo::changeStatus($contentId, 1);
                        $this->log->error('Ошибка генерации аудио озвучки, id текста ' . $video['text_id']);
                        $this->log->error('Ошибка генерации субтитров, id текста ' . $video['text_id']);
                        exec($cmd);
                        return 0;
                    }
                }
            }
        } catch (Exception $e) {
            $this->log->error($e->getMessage());
            $this->log->info('Ошибка форматирования текста: ' . $contentId);
            ContentVideo::changeStatus($contentId, 5);
        } catch (GuzzleException $e) {
            $this->log->error($e->getMessage());
            ContentVideo::changeStatus($contentId, 5);
        }

        if ($this->status_log) {
            $this->log->info('Выполнено ' . date('Y-m-s H:i:s'));
        }

        exec($cmd);
        return 0;
    }
}