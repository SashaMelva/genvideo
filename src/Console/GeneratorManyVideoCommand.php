<?php

namespace App\Console;

use App\Helpers\GeneratorFiles;
use App\Helpers\Speechkit;
use App\Models\ColorBackground;
use App\Models\ContentVideo;
use App\Models\GPTChatRequests;
use App\Models\ListImage;
use App\Models\ListMusic;
use App\Models\ListVideo;
use App\Models\TextVideo;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Database\Capsule\Manager as DB;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GeneratorManyVideoCommand  extends Command
{
    private Logger $log;
    private bool $status_log;

    protected function configure(): void
    {
        $log = new Logger('info');
        $log->pushHandler(new RotatingFileHandler('../var/log/generator-many-video.log', 2, Logger::INFO));
        $log->pushHandler(new StreamHandler('php://stdout'));

        $this->log = $log;
        $this->status_log = true;

        parent::configure();

        $this
            ->setName('generator-many-video')
            ->setDescription('generator-many-video');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        date_default_timezone_set('Europe/Moscow');
        $cmd = '/usr/bin/supervisorctl stop generator-many-video';

        if ($this->status_log) {
            $this->log->info('Начало ' . date('Y-m-s H:i:s'));
        }

        $contentIds = DB::table('content')->select('id')->where([['status_id', '=', 6]])->get()->toArray();

        if ($this->status_log) {
            $this->log->info('Задачи на генерацию: ' . json_encode($contentIds));
        }

        if (empty($contentIds)) {
            $this->log->info('Нет задач на генерацию');
            exec($cmd);
            return 0;
        }

        $contentId = $contentIds[0]->id;

        try {

            $resultName = '';
            $logo = [];
            $slides = [];
            $videoBackground = [];
            $videoStart = [];
            $videoEnd = [];

            if ($this->status_log) {
                $this->log->info('Контент взят на генерацию: ' . $contentId);
            }

            ContentVideo::changeStatus($contentId, 7);

            $gptRequest = GPTChatRequests::findByContentId($contentId);

            if (!empty($gptRequest)) {
                $client = new Client();

                $response = $client->post('http://127.0.0.1:3000/api/main',
                    [
                        'headers' => [
                            'Content-Type' => 'application/json'
                        ],
                        'json' => [
                            'title' => $gptRequest['text_request']
                        ]
                    ]);

                $responseData = json_encode($response->getBody()->getContents(), JSON_UNESCAPED_UNICODE);

                if ($responseData['status'] == 'Ok') {
                    $text = $responseData['response'];
                    TextVideo::updatedContentData($gptRequest['text_id'], $text);
                    GPTChatRequests::changeStatusAndContent($gptRequest['id'], $text);
                }

            } else {
                ContentVideo::changeStatus($contentId, 8);
            }

            $video = ContentVideo::findAllDataByID($contentId);
            $images = ListImage::findAllByContentId($contentId);

            foreach ($images as $image) {
                if ($image['type'] == 'logo') {
                    $logo[] = $image['file_name'];
                }

                if ($image['type'] == 'slide') {
                    $slides[] = $image['file_name'];
                }
            }

            $sound = ListMusic::findAllByContentId($contentId);
            $video['video'] = ListVideo::findAllByContentId($contentId);

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

            ContentVideo::changeStatus($contentId, 3);

            $generatorFiles = new GeneratorFiles($contentId);

            if ($video['status_voice'] == 0 || $video['status_voice'] == 'false' || $video['status_voice'] == 'создано') {

                $fileNameVoice = $contentId . '_' . $video['text_id'];
                $voiceSetting = [
                    'format' => 'mp3',
                    'lang' => $video['language'],
                    'voice' => $video['dictionary_voice_name'],
                    'emotion' => $video['ampula_voice'],
                ];

                $voiceData = (new Speechkit())->generatorWithSubtitles($video['text'], $fileNameVoice, $voiceSetting);

                if ($voiceData['status']) {

                    TextVideo::changeTextStatus($video['text_id'], 'обработано');
                    TextVideo::updateFileVoice($video['text_id'], $fileNameVoice, RELATIVE_PATH_SPEECHKIT . $fileNameVoice . '.' . $voiceSetting['format'], 'успех', $voiceData['time']);
                    TextVideo::updateFileText($video['text_id'], $fileNameVoice, RELATIVE_PATH_TEXT . $fileNameVoice, 'успех');
                    $this->log->info('Успех генерации субтитров, id текста ' . $video['text_id']);
                    $this->log->info('Успех генерации аудио озвучки, id текста ' . $video['text_id']);

                } else {
                    if (isset($voiceData['command'])) {
                        $this->log->error($voiceData['command'] . $video['text_id']);
                    }

                    TextVideo::changeVoiceStatus($video['text_id'], 'ошибка');
                    TextVideo::changeTextStatus($video['text_id'], 'ошибка');
                    ContentVideo::changeStatus($contentId, 5);
                    $this->log->error('Ошибка генерации аудио озвучки, id текста ' . $video['text_id']);
                    $this->log->error('Ошибка генерации субтитров, id текста ' . $video['text_id']);
                    exec($cmd);
                    return 0;
                }
            }

#TODO
//            $voiceData['time'] = '80.040007192089';
//            $fileNameVoice = '89_92';
//            $textData['status'] = true;
//            $textData['name'] = '89_92';

            var_dump($video['content_format']);

            if ($video['type_background'] == 'slide_show' && !empty($voiceData['time'])) {

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
                    $slideshow = $generatorFiles->generatorSladeShow($slidesName, $sound[0]['file_name'], $voiceData['time'], $video['content_format']);

                    if (!$slideshow['status']) {
                        ContentVideo::changeStatus($contentId, 5);
                        $this->log->error($slideshow['command']);
                        $this->log->error('Ошибка генерации слайдшоу');
                        exec($cmd);
                        return 0;
                    }

                    $resultName = $slideshow['fileName'];
                    $this->log->info('Сдайдшоу сгенерировано, имя файла ' . $resultName);

                } else {
                    ContentVideo::changeStatus($contentId, 5);
                    $this->log->error('Изображения не загружены');
                    exec($cmd);
                    return 0;
                }
            }

            if ($video['type_background'] == 'video' && !empty($voiceData['time'])) {
                if (!empty($videoBackground)) {

                    $additionalVideoName = $videoBackground[0];
                    /**Подгоняем видео под формат*/
                    if ($video['content_format'] == '9/16') {
                        $formatVideo = $generatorFiles->generatorVideoFormat($additionalVideoName);

                        if (!$formatVideo['status']) {
                            ContentVideo::changeStatus($contentId, 5);
                            $this->log->error('Ошибка преобразования формата видео');
                            exec($cmd);
                            return 0;
                        }

                        $additionalVideoName = $formatVideo['fileName'];
                        $this->log->info('Успех преобразования формата видео, имя файла ' . $resultName);
                    }

                    $backgroundVideo = $generatorFiles->generatorBackgroundVideoAndMusic($additionalVideoName, $sound[0]['file_name'], $voiceData['time']);

                    if (!$backgroundVideo['status']) {
                        ContentVideo::changeStatus($contentId, 5);
                        $this->log->error('Ошибка генерации фонового видео ' . $backgroundVideo['command']);
                        exec($cmd);
                        return 0;
                    }

                    $resultName = $backgroundVideo['fileName'];
                    $this->log->info('Фоновое видео сгенерировано, имя файла ' . $resultName);

                } else {
                    ContentVideo::changeStatus($contentId, 5);
                    $this->log->error('Видео не загружено');
                    exec($cmd);
                    return 0;
                }
            }

            if (!is_null($video['color_background_id']) && !empty($resultName)) {

                $colorBackground = ColorBackground::findById((int)$video['color_background_id']);
                $background = $generatorFiles->generatorBackground($colorBackground['file_name'], $resultName);

                if (!$background['status']) {
                    ContentVideo::changeStatus($contentId, 5);
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
                    ContentVideo::changeStatus($contentId, 5);
                    $this->log->error('Ошибка прикрепления логотипа');
                    exec($cmd);
                    return 0;
                }

                $resultName = $logoForVideo['fileName'];
                $this->log->info('Логотип прикреплён, имя файла ' . $resultName);
            }

            if (!empty($fileNameVoice)) {

                $voice = $generatorFiles->generatorMusic($fileNameVoice, $resultName);

                if (!$voice['status']) {
                    ContentVideo::changeStatus($contentId, 5);
                    $this->log->error($voice['command']);
                    $this->log->error('Ошибка наложения озвучки текста');
                    exec($cmd);
                    return 0;
                }

                $resultName = $voice['fileName'];
                $this->log->info('Озвучка наложена, имя файла ' . $resultName);
            }

            if ($voiceData['status']) {

                $this->log->info('Название файла субтитров  ' . $voiceData['name']);
                $titers = $generatorFiles->generatorText($resultName, $voiceData['name'], $video['content_format']);

                if (!$titers['status']) {
                    ContentVideo::changeStatus($contentId, 5);
                    $this->log->error('Ошибка наложения субтитров');
                    $this->log->error($titers['command']);
                    exec($cmd);
                    return 0;
                }

                $resultName = $titers['fileName'];
                $this->log->info('Субтитры наложены, имя файла ' . $resultName);
            }

            if (!empty($videoEnd) || !empty($videoStart)) {

                $backgroundVideo = $generatorFiles->mergeVideo($resultName, $video['content_format'], $videoStart[0] ?? null, $videoEnd[0] ?? null);

                if (!$backgroundVideo['status']) {
                    ContentVideo::changeStatus($contentId, 5);
                    $this->log->error('Ошибка склеивания видео');
                    exec($cmd);
                    return 0;
                }

                $resultName = $backgroundVideo['fileName'];
                $this->log->info('Видое склеились, имя файла ' . $resultName);
            }

            ContentVideo::updateContent($contentId, $resultName . '.mp4', RELATIVE_PATH_VIDEO . $resultName . '.mp4', 4);
            $this->log->info('Видео сгенерировано, имя файла ' . $resultName . 'file_path: ' . RELATIVE_PATH_VIDEO . $resultName . '.mp4');

        } catch (Exception $e) {
            $this->log->error($e->getMessage());
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