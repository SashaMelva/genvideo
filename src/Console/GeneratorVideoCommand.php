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
        $log->pushHandler(new RotatingFileHandler('../var/log/generator-video-qq.log', 2, Logger::INFO));
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
        $cmd = '/usr/bin/supervisorctl stop generator-video';

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

        try {

            $resultName = '';
            $logo = [];
            $slides = [];
            $videoBackground = [];
            $videoStart = [];
            $videoEnd = [];

            if ($this->status_log) {
                $this->log->info('Контент взят на генерацию: ' . DIRECTORY_SPEECHKIT . $videoId);
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

            ContentVideo::changeStatus($videoId, 2);

            $generatorFiles = new GeneratorFiles($videoId, $this->log);

            if ($video['status_voice'] == 0 || $video['status_voice'] == 'false') {

                $this->log->info('Начало генерации аудио и субтитров');
                $fileNameVoice = $videoId . '_' . $video['text_id'];
                $voiceSetting = [
                    'format' => 'mp3',
                    'lang' => $video['language'],
                    'voice' => $video['dictionary_voice_name'],
                    'emotion' => $video['ampula_voice'],
                    'delay_between_offers_ms' => is_null($video['delay_between_offers']) ? 0 : $video['delay_between_offers'],
                    'delay_between_paragraphs_ms' => is_null($video['delay_between_paragraphs']) ? 0 : $video['delay_between_paragraphs'],
                    'voice_speed' => is_null($video['voice_speed']) ? '1.0' : $video['voice_speed'],
                    'delay_end_video' => is_null($video['delay_end_video']) ? 3 : $video['delay_end_video']
                ];

                $textVideo = trim($video['initial_text']) . trim($video['text']) . trim($video['end_text']);
                $voiceData = (new Speechkit($this->log))->generatorWithSubtitles($textVideo, $fileNameVoice, $voiceSetting);

                if ($voiceData['status']) {

                    TextVideo::updateFileTextAndStatus($video['text_id'], $fileNameVoice, RELATIVE_PATH_TEXT . $fileNameVoice, '1');
                    TextVideo::updateFileVoice($video['text_id'], $fileNameVoice, RELATIVE_PATH_SPEECHKIT . $fileNameVoice . '.' . $voiceSetting['format'], '1', $voiceData['time']);
                    ContentVideo::changeStatus($videoId, 3);
                    $this->log->info('Успех генерации субтитров, id текста ' . $video['text_id']);
                    $this->log->info('Успех генерации аудио озвучки, id текста ' . $video['text_id']);

                } else {

                    if (isset($voiceData['command'])) {
                        $this->log->error($voiceData['command'] . $video['text_id']);
                    }

                    TextVideo::changeVoiceStatus($video['text_id'], '3');
                    TextVideo::changeTextStatus($video['text_id'], '3');
                    ContentVideo::changeStatus($videoId, 14);
                    $this->log->error('Ошибка генерации аудио озвучки, id текста ' . $video['text_id']);
                    $this->log->error('Ошибка генерации субтитров, id текста ' . $video['text_id']);
                    exec($cmd);
                    return 0;
                }
            }

            $textData = TextVideo::findById($video['text_id'])[0];

            $this->log->info('Продолжительность файла ' . $textData['time_voice']);

            if ($video['type_background'] == 'slide_show' && !is_null($textData['time_voice'])) {

                if (!empty($slides)) {
                    /**Подгоняем картинки под формат*/
                    $slidesName = [];

                    $this->log->info('Список изображений ' . json_encode($slides));
                    foreach ($slides as $slide) {
                        $formatImage = $generatorFiles->generatorImageFormat($slide, $video['content_format']);

                        if (!$formatImage['status']) {
                            $this->log->error('Ошибка преобразования формата изображения ' . $slide . ' => ' . $formatImage['fileName']);
                            $this->log->error($formatImage['command']);
                            $slidesName[] = $slide;
                            $this->log->info('Список изображений ' . json_encode($slidesName));
                            continue;
                        }

                        $slidesName[] = $formatImage['fileName'];
                        $this->log->info('Успех преобразования формата изображения, имя файла ' . $formatImage['fileName']);
                        $this->log->info('Список изображений ' . json_encode($slidesName));
                    }

                    $this->log->info('Список изображений ' . json_encode($slidesName));

                    if (empty($sound[0]['file_name'])) {
                        ContentVideo::changeStatus($videoId, 13);
                        $this->log->error('Фоновая музыка для слайдшоу не указана');
                    }

                    $slideshow = $generatorFiles->generatorSladeShow($slidesName, $sound[0]['file_name'], $textData['time_voice'], $video['content_format']);

                    if (!$slideshow['status']) {
                        ContentVideo::changeStatus($videoId, 13);
                        $this->log->error($slideshow['command']);
                        $this->log->error('Ошибка генерации слайдшоу');
                        exec($cmd);
                        return 0;
                    }

                    $resultName = $slideshow['fileName'];
                    $this->log->info('Сдайдшоу сгенерировано, имя файла ' . $resultName);

                } else {
                    ContentVideo::changeStatus($videoId, 13);
                    $this->log->error('Изображения не загружены');
                    exec($cmd);
                    return 0;
                }
            }

            if ($video['type_background'] == 'video' && !is_null($textData['time_voice'])) {
                if (!empty($videoBackground) && file_exists(DIRECTORY_ADDITIONAL_VIDEO . $videoBackground[0])) {
                    $this->log->info('Начало формирования новго видео');

                    $this->log->info('Видео для склейки ' . json_encode($videoBackground));
                    $additionalVideoName = $videoBackground;

                    if (!file_exists(DIRECTORY_ADDITIONAL_VIDEO . $videoBackground[0])) {
                        ContentVideo::changeStatus($videoId, 13);
                        $this->log->info('Не найдено основное видео');
                        exec($cmd);
                        return 0;
                    }

                    /**Подгоняем видео под формат*/
                    if ($video['content_format'] == '9/16') {
                        $this->log->info('Перобразование формата');
                        $additionalVideoName = [];

                        foreach ($videoBackground as $video) {
                            $formatVideo = $generatorFiles->generatorVideoFormat($video);

                            if (!$formatVideo['status']) {
                                ContentVideo::changeStatus($videoId, 13);
                                $this->log->error('Ошибка преобразования формата видео');
                                exec($cmd);
                                return 0;
                            }

                            $additionalVideoName[] = $formatVideo['fileName'];
                        }

                        $this->log->info('Успех преобразования формата видео, имя файла ' . json_encode($additionalVideoName));
                    }

                    if (!file_exists(DIRECTORY_MUSIC . $sound[0]['file_name'])) {
                        ContentVideo::changeStatus($videoId, 13);
                        $this->log->info('Не найдено аудио');
                        exec($cmd);
                        return 0;
                    }

                    $backgroundVideo = $generatorFiles->generatorBackgroundVideoAndMusic($additionalVideoName, $sound[0]['file_name'], $textData['time_voice']);

                    if (!$backgroundVideo['status']) {
                        ContentVideo::changeStatus($videoId, 13);
                        $this->log->error('Ошибка генерации фонового видео ' . $backgroundVideo['command']);
                        exec($cmd);
                        return 0;
                    }

                    $resultName = $backgroundVideo['fileName'];
                    $this->log->info('Фоновое видео сгенерировано, имя файла ' . $resultName);

                } else {
                    ContentVideo::changeStatus($videoId, 13);
                    $this->log->error('Видео не загружено');
                    exec($cmd);
                    return 0;
                }
            }

            if (!is_null($resultName)) {
                $this->log->info('Создаём превью для видео');
                $preview = $generatorFiles->generatorPreview($resultName, $video['type_background']);

                if (!$preview['status']) {
                    ContentVideo::changeStatus($videoId, 13);
                    $this->log->error('Ошибка генерации превью');
                    exec($cmd);
                    return 0;
                }

                $this->log->info('Превью сгенерировано ' . $resultName);
            }

            if (!is_null($video['color_background_id']) && !is_null($resultName)) {

                $colorBackground = ColorBackground::findById((int)$video['color_background_id']);
                $background = $generatorFiles->generatorBackground($colorBackground['file_name'], $resultName);

                if (!$background['status']) {
                    ContentVideo::changeStatus($videoId, 13);
                    $this->log->error('Ошибка наложения фона видео');
                    exec($cmd);
                    return 0;
                }

                $resultName = $background['fileName'];
                $this->log->info('Фоновое изображение наложено, имя файла ' . $resultName);
            }

            if (!empty($logo) && !is_null($resultName)) {
                $logoForVideo = $generatorFiles->generatorLogo($logo[0], $resultName);

                if (!$logoForVideo['status']) {
                    ContentVideo::changeStatus($videoId, 13);
                    $this->log->error('Ошибка прикрепления логотипа');
                    exec($cmd);
                    return 0;
                }

                $resultName = $logoForVideo['fileName'];
                $this->log->info('Логотип прикреплён, имя файла ' . $resultName);
            }

            if (!empty($textData) && !is_null($resultName)) {

                $voice = $generatorFiles->generatorMusic($textData['file_name_voice'], $resultName);

                if (!$voice['status']) {
                    ContentVideo::changeStatus($videoId, 13);
                    $this->log->error($voice['command']);
                    $this->log->error($voice['command']);
                    $this->log->error('Ошибка наложения озвучки текста');
                    exec($cmd);
                    return 0;
                }

                $resultName = $voice['fileName'];
                $this->log->info('Озвучка наложена, имя файла ' . $resultName);
            }

            if (is_null($textData['subtitles']) || $textData['subtitles']) {

                $this->log->info('Название файла субтитров  ' . $textData['file_name_voice']);

                if (!file_exists(DIRECTORY_TEXT . $textData['file_name_voice'] . '.ass')) {
                    $this->log->info('Файл субтиров не найден  ' . DIRECTORY_TEXT . $textData['file_name_voice'] . '.ass');
                    ContentVideo::changeStatus($videoId, 14);
                    exec($cmd);
                    return 0;
                }

                $titers = $generatorFiles->generatorText($resultName, $textData['file_name_voice'], $video['content_format'], $textData);

                if (!$titers['status']) {
                    ContentVideo::changeStatus($videoId, 13);
                    $this->log->error('Ошибка наложения субтитров');
                    $this->log->error($titers['command']);
                    exec($cmd);
                    return 0;
                }

                $resultName = $titers['fileName'];
                $this->log->info('Субтитры наложены, имя файла ' . $resultName);
            }

            if ((!empty($videoEnd) || !empty($videoStart)) && (file_exists(DIRECTORY_ADDITIONAL_VIDEO . $videoEnd[0]) || file_exists(DIRECTORY_ADDITIONAL_VIDEO . $videoStart[0]))) {
                $this->log->info('Склека видео началась ' . $video['type_background']);
                if ($video['type_background'] == 'video') {
                    $backgroundVideo = $generatorFiles->mergeVideo($resultName, $video['content_format'], $videoStart[0] ?? null, $videoEnd[0] ?? null);
                }
                if ($video['type_background'] == 'slide_show') {
                    $backgroundVideo = $generatorFiles->mergeVideoWithSize($resultName, $video['content_format'], $videoStart[0] ?? null, $videoEnd[0] ?? null);
                }

                if (!$backgroundVideo['status']) {
                    ContentVideo::changeStatus($videoId, 13);
                    $this->log->error('Ошибка склеивания видео ' . $backgroundVideo['command']);
                    exec($cmd);
                    return 0;
                }

                $resultName = $backgroundVideo['fileName'];
                $this->log->info('Видое склеились, имя файла ' . $resultName);
            } else {
                $this->log->error('Видео не найдено');
                ContentVideo::changeStatus($videoId, 13);
            }

            ContentVideo::updateContent($videoId, $resultName . '.mp4', RELATIVE_PATH_VIDEO . $resultName . '.mp4', 4);
            $this->log->info('Видео сгенерировано, имя файла ' . $resultName . 'file_path: ' . RELATIVE_PATH_VIDEO . $resultName . '.mp4');

        } catch
        (Exception $e) {
            $this->log->error($e->getMessage());
            ContentVideo::changeStatus($videoId, 5);
        } catch (GuzzleException $e) {
            $this->log->error($e->getMessage());
            ContentVideo::changeStatus($videoId, 5);
        }

        if ($this->status_log) {
            $this->log->info('Выполнено ' . date('Y-m-s H:i:s'));
        }

        exec($cmd);
        return 0;
    }
}