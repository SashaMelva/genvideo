<?php

namespace App\Console;

use App\Helpers\GeneratorFiles;
use App\Helpers\Speechkit;
use App\Models\ColorBackground;
use App\Models\ContentVideo;
use App\Models\ListImage;
use App\Models\ListMusic;
use App\Models\ListVideo;
use App\Models\TextVideo;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Illuminate\Database\Capsule\Manager as DB;
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;

class GeneratorVideoCommand extends Command
{
    private Logger $log;
    private bool $status_log;

    protected function configure(): void
    {
        $log = new Logger('info');
        $log->pushHandler(new RotatingFileHandler('../var/log/generator-video.log', 2, Logger::INFO));
        $log->pushHandler(new StreamHandler('php://stdout'));

        $this->log = $log;
        $this->status_log = true;

        parent::configure();

        $this
            ->setName('generator-video')
            ->setDescription('generator-video');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        date_default_timezone_set('Europe/Moscow');
        $cmd = '/usr/bin/supervisorctl stop domain-for-get-course';

        if ($this->status_log) {
            $this->log->info('Начало ' . date('Y-m-s H:i:s'));
        }

        $contentIds = DB::table('content')->select('id')->where([['status_id', '=', 1]])->get()->toArray();

        if ($this->status_log) {
            $this->log->info('Задачи на генерацию: ' . json_encode($contentIds));
        }

        if (empty($contentIds)) {
            $this->log->info('Нет задач на генерацию');
            exec($cmd);
            return 0;
        }

        $videoId = $contentIds[0]->id;
//        foreach ($contentIds as $videoId) {

        try {

            $resultName = '';
            $logo = [];
            $slides = [];
            $videoBackground = [];
            $videoStart = [];
            $videoEnd = [];

            if ($this->status_log) {
                $this->log->info('Контент взят на генерацию: ' . $videoId);
            }

            ContentVideo::changeStatus($videoId, 2);

            $video = ContentVideo::findAllDataByID($videoId);
            $images = ListImage::findAllByContentId($videoId);

            foreach ($images as $image) {
                if ($image['type'] == 'logo') {
                    $logo[] = $image['file_name'];
                }

                if ($image['type'] == 'slide') {
                    $slides[] = $image['file_name'];
                }
            }

            $sound = ListMusic::findAllByContentId($videoId);
            $video['video'] = ListVideo::findAllByContentId($videoId);

            foreach ($video['video'] as $additionalVideo) {
                if ($additionalVideo['type'] == 'content') {
                    $videoBackground[] = $additionalVideo['file_name'];
                }

                if ($additionalVideo['type'] == 'start') {
                    $videoStart[] = $additionalVideo['file_name'];
                }

                if ($additionalVideo['type'] == 'end') {
                    $videoEnd[] = $additionalVideo['file_name'];
                }
            }

            ContentVideo::changeStatus($videoId, 3);

            $generatorFiles = new GeneratorFiles($videoId);

            if ($video['status_voice'] == 0 || $video['status_voice'] == 'false' || $video['status_voice'] == 'создано') {

                $fileNameVoice = $videoId . '_' . $video['text_id'];
                $voiceSetting = [
                    'format' => 'mp3',
                    'lang' => $video['language'],
                    'voice' => $video['dictionary_voice_name'],
                    'emotion' => $video['ampula_voice'],
                ];

                $voiceData = (new Speechkit())->generator($video['text'], $fileNameVoice, $voiceSetting);

                if ($voiceData['status']) {

                    TextVideo::changeTextStatus($video['text_id'], 'обработано');
                    TextVideo::updateFileVoice($video['text_id'], $fileNameVoice, RELATIVE_PATH_SPEECHKIT . $fileNameVoice . '.' . $voiceSetting['format'], true, $voiceData['time']);
                    $this->log->info('Успех генерации аудио озвучки, id текста ' . $video['text_id']);

                } else {

                    TextVideo::changeVoiceStatus($video['text_id'], 'ошибка');
                    ContentVideo::changeStatus($videoId, 5);
                    $this->log->error('Ошибка генерации аудио озвучки, id текста ' . $video['text_id']);
                    exec($cmd);
                    return 0;
                }
            }

            if ($video['status_text'] == 0 || $video['status_text'] == 'false' || $video['status_text'] == 'создано') {

                TextVideo::changeTextStatus($video['text_id'], 'в обработке');
                $textData = $generatorFiles->generatorTextForTitre($video['text'], $video['text_id']);

                if ($textData['status']) {

                    TextVideo::changeTextStatus($video['text_id'], 'обработано');
                    $this->log->info('Успех генерации субтитров, id текста ' . $video['text_id']);

                } else {

                    TextVideo::changeTextStatus($video['text_id'], 'ошибка');
                    ContentVideo::changeStatus($videoId, 5);
                    $this->log->error('Ошибка генерации субтитров, id текста ' . $video['text_id']);
                    exec($cmd);
                    return 0;
                }
            }

            if ($video['type_background'] == 'slide_show' && !empty($voiceData['time'])) {
                $slideshow = $generatorFiles->generatorSladeShow($slides, $sound[0]['file_name'], $voiceData['time']);

                if (!$slideshow['status']) {
                    ContentVideo::changeStatus($videoId, 5);
                    $this->log->error('Ошибка генерации слайдшоу');
                    exec($cmd);
                    return 0;
                }

                $resultName = $slideshow['fileName'];
                $this->log->info('Сдайдшоу сгенерировано, имя файла ' . $resultName);
            }

            if ($video['type_background'] == 'video' && !empty($voiceData['time'])) {

                if (!empty($videoBackground)) {
                    $backgroundVideo = $generatorFiles->generatorBackgroundVideoAndMusic($videoBackground[0], $sound[0]['file_name'], $voiceData['time']);

                    if (!$backgroundVideo['status']) {
                        ContentVideo::changeStatus($videoId, 5);
                        $this->log->error('Ошибка генерации фонового видео');
                        exec($cmd);
                        return 0;
                    }

                    $resultName = $backgroundVideo['fileName'];
                    $this->log->info('Фоновое видео сгенерировано, имя файла ' . $resultName);

                } else {
                    ContentVideo::changeStatus($videoId, 5);
                    $this->log->error('Видео не загружено');
                    exec($cmd);
                    return 0;
                }
            }

            if (!is_null($video['color_background_id'])) {

                $colorBackground = ColorBackground::findById((int)$video['color_background_id']);
                $background = $generatorFiles->generatorBackground($colorBackground['file_name'], $resultName);

                if (!$background['status']) {
                    ContentVideo::changeStatus($videoId, 5);
                    $this->log->error('Ошибка наложения фона видео');
                    exec($cmd);
                    return 0;
                }

                $resultName = $background['fileName'];
                $this->log->info('Фоновое изображение наложено, имя файла ' . $resultName);
            }

            if (!empty($logo)) {
                $logoForVideo = $generatorFiles->generatorLogo($logo[0], $resultName);

                if (!$logoForVideo['status']) {
                    ContentVideo::changeStatus($videoId, 5);
                    $this->log->error('Ошибка прикрепления логотипа');
                    exec($cmd);
                    return 0;
                }

                $resultName = $logoForVideo['fileName'];
                $this->log->info('Логотип прикреплён, имя файла ' . $resultName);
            }

            if (!empty($videoEnd) || !empty($videoStart)) {
                $backgroundVideo = $generatorFiles->mergeVideo($resultName, $videoStart[0] ?? null, $videoEnd[0] ?? null);

                if (!$backgroundVideo['status']) {
                    ContentVideo::changeStatus($videoId, 5);
                    $this->log->error('Ошибка склеивания видео');
                    exec($cmd);
                    return 0;
                }

                $resultName = $backgroundVideo['fileName'];
                $this->log->info('Видое склеились, имя файла ' . $resultName);
            }

            if (!empty($fileNameVoice)) {

                $voice = $generatorFiles->generatorMusic($fileNameVoice, $resultName);

                if (!$voice['status']) {
                    ContentVideo::changeStatus($videoId, 5);
                    $this->log->error('Ошибка наложения озвучки текста');
                    exec($cmd);
                    return 0;
                }

                $resultName = $voice['fileName'];
                $this->log->info('Озвучка наложена, имя файла ' . $resultName);
            }

            if ($textData['status']) {

                $titers = $generatorFiles->generatorText($resultName, '7_10');

                if (!$titers['status']) {
                    ContentVideo::changeStatus($videoId, 5);
                    $this->log->error('Ошибка наложения субтитров');
                    exec($cmd);
                    return 0;
                }

                $resultName = $titers['fileName'];
                $this->log->info('Субтитры наложены, имя файла ' . $resultName);
            }

            ContentVideo::updateContent($videoId, $resultName . '.mp4', RELATIVE_PATH_VIDEO . $resultName . '.mp4', 4);
            $this->log->info('Видео сгенерировано, имя файла ' . $resultName . 'file_path: ' . RELATIVE_PATH_VIDEO . $resultName . '.mp4');

        } catch (Exception $e) {
            $this->log->error($e->getMessage());
            ContentVideo::changeStatus($videoId, 5);
        } catch (GuzzleException $e) {
            $this->log->error($e->getMessage());
            ContentVideo::changeStatus($videoId, 5);
        }
//        }

        if ($this->status_log) {
            $this->log->info('Выполнено ' . date('Y-m-s H:i:s'));
        }

        exec($cmd);
        return 0;
    }
}