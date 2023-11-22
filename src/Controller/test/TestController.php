<?php

namespace App\Controller\test;

use App\Controller\UserController;
use App\Helpers\CheckTokenExpiration;
use App\Helpers\GeneratorFiles;
use App\Helpers\Speechkit;
use App\Helpers\UploadFile;
use App\Models\ColorBackground;
use App\Models\ContentVideo;
use App\Models\GPTChatRequests;
use App\Models\ListImage;
use App\Models\ListMusic;
use App\Models\ListVideo;
use App\Models\TextVideo;
use App\Models\User;
use Exception;
use getID3;
use Google_Client;
use Google_Service_YouTube;
use Google_Service_YouTube_Video;
use Google_Service_YouTube_VideoSnippet;
use Google_Service_YouTube_VideoStatus;
use GuzzleHttp\Client;
use Illuminate\Database\Capsule\Manager as DB;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Spatie\SimpleExcel\SimpleExcelReader;

class TestController extends UserController
{
    private Logger $log;
    private Client $client;


    /**
     * @throws Exception
     */
    public function action(): ResponseInterface
    {
        $log = new Logger('info');
        $log->pushHandler(new RotatingFileHandler('/var/www/genvi-api/var/log/test.log', 2, Logger::INFO));
        $log->pushHandler(new StreamHandler('php://stdout'));
        $this->log = $log;
        $this->status_log = true;

        $video = ContentVideo::findAllDataByID(435);

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

//        $textVideo = $video['initial_text'] . $video['text'] . $video['end_text'];
//        var_dump($textVideo);
//        var_dump(explode("\n", $textVideo));
//        var_dump(str_contains($textVideo, '\n'));
        exit();
//        $subtitles = [
//            ["text" => "По психосоматике астма может быть связана с психологическими и эмоциональными факторами.", "time" => 6240],
//            ["text" => "Хотя астма в основном рассматривается как хроническое воспалительное заболевание дыхательных путей, психосоматический подход подчеркивает влияние психологических состояний на проявление и усиление симптомов астмы.", "time" => 13104],
//            ["text" => "Стресс, тревожность, депрессия и другие эмоциональные факторы могут усиливать симптомы астмы у некоторых людей.", "time" => 7656], ["text" => "Это может быть связано с изменениями в дыхательной функции, уровнями воспаления или сокращениями бронхиальных мышц под воздействием стресса и эмоционального напряжения.", "time" => 10800],
//            ["text" => "Кроме того, некоторые люди могут испытывать \"стресс-индуцированную астму\", когда стресс или эмоциональное напряжение являются прямыми триггерами для приступов удушья и других симптомов астмы.", "time" => 12792],
//            ["text" => "Важно отметить, что психосоматический подход не исключает физиологические аспекты астмы, такие как воспаление дыхательных путей и гиперреактивность бронхов.", "time" => 10224],
//            ["text" => "Однако он подчеркивает важность понимания влияния психологических факторов на заболевание и возможность использования психологических методов в комплексном лечении астмы.", "time" => 10296],
//            ["text" => "Люди с астмой могут иметь пользу от поддержки психолога или психотерапевта для управления стрессом, тревожностью и другими эмоциональными аспектами, которые могут влиять на их состояние здоровья.", "time" => 12960],
//            ["text" => "Также медитация, релаксация, практики осознанности и другие методы саморегуляции могут быть полезны в управлении психологическими аспектами астмы.", "time" => 10104]
//        ];
//
//        file_put_contents(DIRECTORY_TEXT . 222 . '.srt', $this->getFilesSrt($subtitles, 40000));

        //        unlink(DIRECTORY_PREVIEW . $firstPreviewName);

//        if (count($textArray) == 1) {
//
//            $this->log->info('Одна строчка текста, добавили субтитр на первый кадр 5 секунды видео');
//            $ffmpeg = 'ffmpeg -ss 00:00:05 -i ' . DIRECTORY_VIDEO . $videoName . ' -frames:v 1 -vf "drawtext=fontfile=' . DIRECTORY_FONTS . 'arial_bold.ttf: text=' . $textArray[0] . ': fontcolor=black:line_spacing=0:  fontsize=84: box=0: boxcolor=yellow: boxborderw=10: x=20:y=60" -y  ' . DIRECTORY_PREVIEW . $resultImage;
//            $errors = shell_exec($ffmpeg . ' -hide_banner -loglevel error 2>&1');
//            $this->log->info($ffmpeg);
//
//        } else {
//            $this->log->info('Берём первую строчку текста и выризаем из видео кадр');
//            $ffmpeg = 'ffmpeg -ss 00:00:05 -i ' . DIRECTORY_VIDEO . $videoName . ' -frames:v 1 -vf "drawtext=fontfile=' . DIRECTORY_FONTS . 'arial_bold.ttf: text=' . $textArray[0] . ': fontcolor=black:line_spacing=0:  fontsize=84: box=0: boxcolor=yellow: boxborderw=10: x=20:y=60" -y  ' . DIRECTORY_PREVIEW . 'preview_0.jpg';
//            $errors = shell_exec($ffmpeg . ' -hide_banner -loglevel error 2>&1');
//            $this->log->info($ffmpeg);
//
//            $marginTop = 110;
//            unset($textArray[0]);
//            foreach ($textArray as $key => $textValue) {
//                $marginTop += 80;
//                if ($key + 2 == count($textArray)) {
//                    $this->log->info('Последняя строчка текста');
//                    $ffmpeg = 'ffmpeg -ss 00:00:05 -i ' . DIRECTORY_PREVIEW . 'preview_' . $key - 1 . '.jpg -frames:v 1 -vf "drawtext=fontfile=' . DIRECTORY_FONTS . 'arial_bold.ttf: text=' . $textArray[0] . ': fontcolor=black:line_spacing=0:  fontsize=84: box=0: boxcolor=yellow: boxborderw=10: x=20:y=' . $marginTop . '" -y  ' . DIRECTORY_PREVIEW . $resultImage;
//                    $errors = shell_exec($ffmpeg . ' -hide_banner -loglevel error 2>&1');
//                    $this->log->info($ffmpeg);
//                    break;
//                }
//
//                if ($key == 1) {
//                    $this->log->info('Вторая строчка текста');
//                    $marginTop += 10;
//                }
//
//                $this->log->info('Строчка текста ' . $key);
//                $ffmpeg = 'ffmpeg -ss 00:00:05 -i ' . DIRECTORY_PREVIEW . 'preview_' . $key - 1 . '.jpg -frames:v 1 -vf "drawtext=fontfile=' . DIRECTORY_FONTS . 'arial_bold.ttf: text=' . $textArray[0] . ': fontcolor=black:line_spacing=0:  fontsize=84: box=0: boxcolor=yellow: boxborderw=10: x=20:y=' . $marginTop . '" -y  ' . DIRECTORY_PREVIEW . 'preview_' . $key . '.jpg';
//                $errors = shell_exec($ffmpeg . ' -hide_banner -loglevel error 2>&1');
//                $this->log->info($ffmpeg);
//            }
//        }


//        $videoId = 174;
//        $resultName = '174_text';
//        $video = ContentVideo::findAllDataByID($videoId);
//        $video['video'] = ListVideo::findAllByContentId($videoId);
//
//        foreach ($video['video'] as $additionalVideo) {
//            if ($additionalVideo['type'] == 'content') {
//                $videoBackground[] = $additionalVideo['file_name'];
//            }
//
//            if ($additionalVideo['type'] == 'start') {
//                $videoStart[] = $additionalVideo['file_name'];
//            }
//
//            if ($additionalVideo['type'] == 'end') {
//                $videoEnd[] = $additionalVideo['file_name'];
//            }
//        }
//
//        $generatorFiles = new GeneratorFiles($videoId, $this->log);
//        if ($video['type_background'] == 'video') {
//            $backgroundVideo = $generatorFiles->mergeVideo($resultName, $video['content_format'], $videoStart[0] ?? null, $videoEnd[0] ?? null);
//        } else {
//            $backgroundVideo = $generatorFiles->mergeVideoWithSize($resultName, $video['content_format'], $videoStart[0] ?? null, $videoEnd[0] ?? null);
//        }
//
//        var_dump($backgroundVideo);

//        $path = DIRECTORY_EXCEL_IMPORT . '123.xlsx';
//        $excelRows = SimpleExcelReader::create($path)->formatHeadersUsing(fn($header) => mb_strtolower(trim($header)));
//        $allRows = $excelRows->getRows()->toArray();
//
//        foreach ($allRows as $item) {
//            DB::table('GPT_chat_cabinet')->insert(['email' => $item['почта'], 'password' => $item['пароль'], 'api_key' => $item['api'], 'status_work' => 1, 'status_cabinet' => true]);
//        }

//        for ($i = 1; $i < 52; $i ++) {
//            DB::table('list_cabinet_for_proxy')->insert(['id_cabinet' => $i, 'id_proxy' => 1]);
//        }
        exit();


        $this->client = new Client();

        $this->log->info('Начало ' . date('Y-m-s H:i:s'));


        $texxtData = TextVideo::findOne(207);

        $text = str_replace('\n\n', '\n', $texxtData['text']);
        $result = $this->spillSubtitlesParagraph($text);
        exit();
        $text = 'Я встречался с девушкой около года Всё была хорошо, но в один момент она просто сказала что меня не любит, Причину она не могла назвать потому что не знала, Пообсуждая с ней это мы пришли к выводу что у неё много проблем и она их не кому не доверяет ( даже мне когда был её парнем) и это так накопилось что взорвала её. Она сказала что может года через 2 когда всё пройдёт, она ещё напишит мне и мы снова всё начнём, но чтобы я не надеелся и отпустил . А я не могу её отпустить хотя прошло 3 месяца, но вот не могу. Мы не общаемся ничего. Но вчера вечером мы как-то по переписывались. Она сказала что к ней много кто подкатывает и она всех отшивает и что однажды она целовалась с пацаном и он её лапал и она была не против и хотела большего , но этого не случилось. Ранее СМИ писали, что он просил подчинённых делать фейковые аккаунты в Твиттере, чтобы отвечать критикам  Блойс рассказал, что ему важно чтобы шоу HBO любили все, поэтому одно время негативные отзывы его сильно расстраивали его сильно расстраивали его сильно расстраивали      ';
        var_dump(1);
//        $voiceSetting = [
//            'format' => 'mp3',
//            'lang' => $video['language'],
//            'voice' => $video['dictionary_voice_name'],
//            'emotion' => $video['ampula_voice'],
//            'delay_between_offers_ms' => is_null($video['delay_between_offers']) ? 0 : $video['delay_between_offers'],
//            'delay_between_paragraphs_ms' => is_null($video['delay_between_paragraphs']) ? 0 : $video['delay_between_paragraphs'],
//            'voice_speed' => is_null($video['voice_speed']) ? '1.0' : $video['voice_speed'],
//        ];

        $voiceSetting = [
            'format' => 'mp3',
            'lang' => 'ru-RU',
            'voice' => 'ermil',
            'emotion' => 'good',
            'delay_between_offers_ms' => 5000,
            'voice_speed' => '1.0'
        ];
        $result = $this->spillSubtitlesOffers($text);

        $data = $this->SplitMp3($result, 'RESULT_endNew', $voiceSetting, $voiceSetting['delay_between_offers_ms']);

        return $this->respondWithData($data);
    }

    private function getFilesSrt(array $text, float $delayBetweenOffersMs): string
    {
        $arr = [];
        $allTime = 0;
        $counterGlobal = 1;
        $this->log->info('Массив субтитры ' . json_encode($text, JSON_UNESCAPED_UNICODE));

        foreach ($text as $item) {
            $countSubtitles = floor($item['time'] / 3000);
            $textShorts = $this->shortText($item['text'], $countSubtitles);
            $shortTimePiece = round($item['time'] / $countSubtitles, 2);
            $shortTime = 0;
            $counter = 0;

            while ($counter < $countSubtitles) {

                $arr[] = ($counterGlobal) . "\r\n" . str_replace('.', ',', $this->formatMilliseconds($shortTime + $allTime))
                    . ' --> ' . str_replace('.', ',', $this->formatMilliseconds($shortTime + $allTime + $shortTimePiece)) . "\r\n" . $textShorts[$counter] . "\r\n";

                $shortTime = $shortTime + $shortTimePiece;
                $counterGlobal += 1;
                $counter += 1;
            }

            $allTime = $item['time'] + $allTime + $delayBetweenOffersMs;
        }

        return implode("\r\n", $arr);
    }

    private function SplitMp3($Mp3Files, $number, array $voiceSetting, int $delayBetween): array
    {
        try {

            $tmp_array = [];
            $subtitles = [];
            $nameAudio = [];

            $this->log->info('Отправка запросов на синтез');
            foreach ($Mp3Files as $key => $item) {

                $this->log->info($item['text']);
                $response = $this->response($item['text'], $voiceSetting);
                $length = file_put_contents(DIRECTORY_SPEECHKIT . $number . '_' . $key . '.mp3', $response);

                $getID3 = new getID3;
                $file = $getID3->analyze(DIRECTORY_SPEECHKIT . $number . '_' . $key . '.mp3');
                $seconds = $file['playtime_seconds'];
                $this->log->info('Начало ');
                $subtitles[] = [
                    'text' => $item['text'],
                    'time' => $seconds * 1000,
                    'merge' => $item['merge'],
                ];

                if (!$length) {
                    return ['status' => false, 'files' => []];
                }

                $tmp_array[] = DIRECTORY_SPEECHKIT . $number . '_' . $key . '.mp3';
                $nameAudio[] = ['nameAudio' => $number . '_' . $key, 'merge' => $item['merge']];
            }
            $this->log->info('Названия полученныйх видео ' . json_encode($tmp_array, true));
            $this->log->info('Название файлов на удаление ' . json_encode($nameAudio, true));
            $this->log->info('Получили массив субтитров ' . json_encode($subtitles, true));
            $voices = implode('|', $tmp_array);

            $this->log->info('Начало склейки аудио с задержкой');
            if ($delayBetween > 0) {
                $arrayLongAudio = [];
                $mergesAudio = [];
                $this->log->info(json_encode($nameAudio, JSON_UNESCAPED_UNICODE));

                foreach ($nameAudio as $key => $audio) {
                    $audioName = DIRECTORY_SPEECHKIT . $audio['nameAudio'] . '.mp3';
                    $this->log->info('Название файла ' . $audioName);

                    if ($audio['merge']) {

                        $this->log->info('Файл является частью для склейки ' . $audioName);
                        $mergesAudio[] = DIRECTORY_SPEECHKIT . $audio['nameAudio'] . '.mp3';
                        $this->log->info('Масиив с файлами для склейки' . json_encode($mergesAudio, true));

                        if (isset($nameAudio[$key + 1])) {
                            $this->log->info('Это последний фал для склеки? ' . !$nameAudio[$key + 1]['merge']);
                            if (!$nameAudio[$key + 1]['merge']) {
                                $this->log->info('Последний файл для склеки');
                                $audioName = DIRECTORY_SPEECHKIT . $audio['nameAudio'] . '_merges.mp3';

                                $ffmpeg = 'ffmpeg -i "concat:' . implode('|', $mergesAudio) . '"  -acodec copy -c:a libmp3lame ' . $audioName;
                                $this->log->info($ffmpeg);
                                shell_exec($ffmpeg . ' -hide_banner -loglevel error 2>&1');

                                $outputAudio = DIRECTORY_SPEECHKIT . $audio['nameAudio'] . '_merges_long.mp3';
                                $this->log->info('Добавление файлу паузу в начале');
                                $ffmpegPause = 'ffmpeg -i ' . $audioName . ' -af adelay=' . $delayBetween . ' ' . $outputAudio;
                                $this->log->info($ffmpegPause);
                                shell_exec($ffmpegPause . ' -hide_banner -loglevel error 2>&1');

                                $mergesAudio = [];
                                $arrayLongAudio[] = $outputAudio;
                            }
                            $this->log->info('Это не последний файлм для склеки');
                        } else {
                            $this->log->info('Последние файлы для склеки' . json_encode($mergesAudio, true));

                            $audioName = DIRECTORY_SPEECHKIT . $audio['nameAudio'] . '_merges.mp3';
                            $ffmpeg = 'ffmpeg -i "concat:' . implode('|', $mergesAudio) . '"  -acodec copy -c:a libmp3lame ' . $audioName;
                            $this->log->info($ffmpeg);
                            shell_exec($ffmpeg . ' -hide_banner -loglevel error 2>&1');

                            $outputAudio = DIRECTORY_SPEECHKIT . $audio['nameAudio'] . '_merges_long.mp3';
                            $this->log->info('Добавление файлу паузу в начале');
                            $ffmpegPause = 'ffmpeg -i ' . $audioName . ' -af adelay=' . $delayBetween . ' ' . $outputAudio;
                            $this->log->info($ffmpegPause);
                            shell_exec($ffmpegPause . ' -hide_banner -loglevel error 2>&1');

                            $mergesAudio = [];
                            $arrayLongAudio[] = $outputAudio;
                        }

                        continue;
                    }

                    $this->log->info('Формирование аудио с пустотой спереди');
                    $outputAudio = $audio['nameAudio'] . '_long.mp3';
                    $ffmpeg = 'ffmpeg -i ' . $audioName . ' -af adelay=' . $delayBetween . ' ' . DIRECTORY_SPEECHKIT . $outputAudio;
                    $this->log->info($ffmpeg);
                    shell_exec($ffmpeg . ' -hide_banner -loglevel error 2>&1');
                    $arrayLongAudio[] = DIRECTORY_SPEECHKIT . $outputAudio;
                }


                $tmp_array = array_merge($tmp_array, $arrayLongAudio);
                $voices = implode('|', $arrayLongAudio);
            }

            $this->log->info('Склеиваеи все аудио ');
            $resultNameAllFiles = $number . '_all';
            $ffmpeg = 'ffmpeg -i "concat:' . $voices . '"  -acodec copy -c:a libmp3lame ' . DIRECTORY_SPEECHKIT . $resultNameAllFiles . '.mp3';
            $this->log->info($ffmpeg);
            shell_exec($ffmpeg . ' -hide_banner -loglevel error 2>&1');
            $tmp_array[] = DIRECTORY_SPEECHKIT . $resultNameAllFiles . '.mp3';

            $this->log->info('Отрезаем спереди файла пустоту');
            $cutFrontVideo = $resultNameAllFiles . '_cut';
            $ffmpegShortAudio = 'ffmpeg -i ' . DIRECTORY_SPEECHKIT . $resultNameAllFiles . '.mp3 -ss ' . (int)($delayBetween / 1000) . ' -acodec copy -y ' . DIRECTORY_SPEECHKIT . $cutFrontVideo . '.mp3';
            $this->log->info($ffmpegShortAudio);
            shell_exec($ffmpegShortAudio . ' -hide_banner -loglevel error 2>&1');
            $tmp_array[] = DIRECTORY_SPEECHKIT . $cutFrontVideo . '.mp3';

            $this->log->info('Добавлям в конец файла две секунды');
            $ffmpegShortAudioResult = 'ffmpeg -i ' . DIRECTORY_SPEECHKIT . $cutFrontVideo . '.mp3 -af "apad=pad_dur=2" -y ' . DIRECTORY_SPEECHKIT . $number . '.mp3';
            $this->log->info($ffmpegShortAudioResult);
            shell_exec($ffmpegShortAudioResult . ' -hide_banner -loglevel error 2>&1');


            $this->log->info('Генерируем файл субтитрв');
            /**для субтитров*/
            $length = file_put_contents(DIRECTORY_TEXT . $number . '.srt', $this->getFilesSrt($this->mergesSubtitles($subtitles), $delayBetween));

            $this->log->info('Преабразуем файл субтитров в формат ass');
            $ffmpeg = 'ffmpeg -i ' . DIRECTORY_TEXT . $number . '.srt -y ' . DIRECTORY_TEXT . $number . '.ass';
            $errors = shell_exec($ffmpeg . ' -hide_banner -loglevel error 2>&1');
            return ['status' => true, 'files' => $tmp_array, 'command' => $ffmpeg];
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    private function mergesSubtitles(array $texts): array
    {
        $result = [];
        $sumTime = 0;
        $sumText = '';
        foreach ($texts as $key => $text) {
            if ($text['merge']) {
                $sumTime += $text['time'];
                $sumText .= ' ' . $text['text'];

                if (!$texts[$key + 1]['merge']) {
                    $result[] = ['text' => $sumText, 'time' => $sumTime];
                    $sumTime = 0;
                    $sumText = '';
                }

                continue;
            }
            $result[] = ['text' => $text['text'], 'time' => $text['time']];
        }

        $this->log->info('Получили преобразованные субтитры ' . json_encode($result, true));
        return $result;
    }


    /** Распределение текста по предложениям длинною не более 250 симвволов, не теряя смысловой нагрузки */
    private function spillSubtitlesOffers(string $text): array
    {
        $this->log->info('Форматирование текста по предложениям');
        $desc = trim($text);
        $desc = preg_replace("[\r\n]", " ", $desc);
        $desc = preg_replace("[\n\n]", " ", $desc);
        $textArray = explode('.', $desc);

        if (iconv_strlen($textArray[count($textArray) - 1]) >= 0 && iconv_strlen($textArray[count($textArray) - 1]) < 2) {
            unset($textArray[count($textArray) - 1]);
        }

        $countChar = 250;
        $result = [];

        /** Проверка остальных предложения на количество символов */
        for ($i = 0; $i < count($textArray); $i++) {

            if (iconv_strlen(trim($textArray[$i])) > $countChar) {
                $textLongArray = explode(',', trim($textArray[$i]));
                $textLong = trim($textLongArray[0]) . ', ';
                unset($textLongArray[0]);

                $countLong = count($textLongArray);

                for ($j = 1; $j <= $countLong; $j++) {
                    if (iconv_strlen($textLong) + iconv_strlen($textLongArray[$j]) > $countChar) {
                        $result[] = ['text' => trim($textLong), 'merge' => true];
                        $textLong = '';
                    }

                    $textLong .= trim($textLongArray[$j]) . ', ';

                    if ($j == $countLong) {
                        $result[] = ['text' => trim($textLong), 'merge' => true];
                    }
                }
            } else {
                $result[] = ['text' => trim($textArray[$i]) . '.', 'merge' => false];
            }
        }
        $this->log->info("Получили отворматированный текст");
        $this->log->info(json_encode($result, true));
        return $result;
    }

    private function spillSubtitlesParagraph(string $text): array
    {
        $this->log->info('Форматирование текста по предложениям');
        $desc = trim($text);
        $textArray = explode('\n', $desc);


        foreach ($textArray as $value) {
            print_r([iconv_strlen($value), $value]);
        }
        $countChar = 250;
        $result = [];


        for ($l = 0; $l < count($textArray); $l++) {

            if (iconv_strlen(trim($textArray[$l])) > $countChar) {
                $textLongArrayParagraph = explode('.', trim($textArray[$l]));
                if (iconv_strlen($textLongArrayParagraph[count($textLongArrayParagraph) - 1]) >= 0 && iconv_strlen($textLongArrayParagraph[count($textLongArrayParagraph) - 1]) < 2) {
                    unset($textLongArrayParagraph[count($textLongArrayParagraph) - 1]);
                }
                $countLongParagraph = count($textLongArrayParagraph);

                /** Проверка остальных предложения на количество символов */
                for ($i = 0; $i < $countLongParagraph; $i++) {

                    if (iconv_strlen(trim($textLongArrayParagraph[$i])) > $countChar) {
                        $textLongArray = explode(',', trim($textLongArrayParagraph[$i]));
                        $textLong = trim($textLongArray[0]) . ', ';
                        unset($textLongArray[0]);

                        $countLong = count($textLongArray);

                        for ($j = 1; $j <= $countLong; $j++) {
                            if (iconv_strlen($textLong) + iconv_strlen($textLongArray[$j]) > $countChar) {
                                $result[] = ['text' => trim($textLong), 'merge' => true];
                                $textLong = '';
                            }

                            $textLong .= trim($textLongArray[$j]) . ', ';

                            if ($j == $countLong) {
                                $result[] = ['text' => trim($textLong), 'merge' => true];
                            }
                        }
                    } else {
                        $result[] = ['text' => trim($textLongArrayParagraph[$i]) . '.', 'merge' => true];
                    }
                }
            } else {
                $result[] = ['text' => trim($textArray[$l]), 'merge' => false, 'len' => iconv_strlen(trim($textArray[$l]))];
            }

        }

        $this->log->info("Получили отформатированный текст");
        $this->log->info(json_encode($result, true));
        return $result;
    }


    /** Распределение текста по 250 симвволов, не теряя смысловой нагрузки */
    private
    function spillSubtitles(string $text): array
    {
        $desc = $text . ' ';
        $desc = preg_replace("[\r\n]", " ", $desc);
        $textArray = explode('.', $desc);
        $countChar = 250;
        $result = [];
        $text = trim($textArray[0]) . '. ';

        /** Проверка первого предложения на количество символов */
        if ($text > $countChar) {

            $textLongArray = explode(',', trim($text));
            $textLong = trim($textLongArray[0]) . ', ';
            unset($textLongArray[0]);
            $countLong = count($textLongArray);

            for ($j = 1; $j <= $countLong; $j++) {
                if (iconv_strlen($textLong) + iconv_strlen($textLongArray[$j]) > $countChar) {
                    $result[] = ['text' => trim($textLong) . ', ', 'merge' => true];
                    $textLong = '';
                }

                $textLong .= trim($textLongArray[$j]);

                if ($j == $countLong) {
                    $result[] = ['text' => trim($textLong), 'merge' => true];
                }
            }

            $text = '';
        }

        unset($textArray[0]);
        $count = count($textArray);

        /** Проверка остальных предложения на количество символов */
        for ($i = 1; $i <= $count; $i++) {
            if (iconv_strlen(trim($text)) + iconv_strlen(trim($textArray[$i])) > $countChar) {
                if (iconv_strlen(trim($textArray[$i])) > $countChar) {

                    $textLongArray = explode(',', trim($textArray[$i]));
                    $textLong = trim($textLongArray[0]) . ', ';
                    unset($textLongArray[0]);
                    $countLong = count($textLongArray);

                    for ($j = 1; $j <= $countLong; $j++) {
                        if (iconv_strlen($textLong) + iconv_strlen($textLongArray[$j]) > $countChar) {
                            $result[] = ['text' => trim($textLong) . ', ', 'merge' => true];
                            $textLong = '';
                        }

                        $textLong .= trim($textLongArray[$j]) . ', ';

                        if ($j == $countLong) {
                            $result[] = ['text' => trim($textLong) . ', ', 'merge' => true];
                        }
                    }
                    $text = '';
                    continue;
                }

                $rep = str_replace('..', '.', trim($text) . '. ');
                $rep = str_replace('!.', '.', $rep);
                $result[] = ['text' => str_replace('? .', '. ', $rep), 'merge' => false];
                $text = '';
            }

            $text .= trim($textArray[$i]) . '. ';

            if ($i == $count) {
                $rep = str_replace('. .', '.', trim($text) . '.');
                $rep = str_replace('! .', '!', $rep);
                $rep = str_replace('? .', '?', $rep);
                $rep = str_replace('..', '.', $rep);
                $rep = str_replace('!.', '!', $rep);
                $result[] = ['text' => str_replace('?.', '?', $rep), 'merge' => false];
            }
        }

        return $result;
    }

    private
    function response(string $text, array $voiceSetting): bool|string
    {
        try {
            $tokenData = DB::table('token_yandex')->where([['id', '=', 1]])->get()->toArray()[0];
            $token = trim($tokenData->token);

            $response = $this->client->post('https://tts.api.cloud.yandex.net/speech/v1/tts:synthesize',
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $token,
                        'x-folder-id' => 'b1glckrv5eg7s4kkhtpn',
                        'Content-Type' => 'application/x-www-form-urlencoded'
                    ],
                    'form_params' => [
                        'text' => $text,
                        'format' => $voiceSetting['format'],
                        'lang' => $voiceSetting['lang'],
                        'voice' => $voiceSetting['voice'],
                        'emotion' => $voiceSetting['emotion'],
                        'speed' => $voiceSetting['voice_speed'] ?? '1.0',
                        'folderId' => 'b1glckrv5eg7s4kkhtpn'
                    ]
                ]);

            if ($response->getStatusCode() !== 200) {
                return false;
            }

            return $response->getBody()->getContents();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    private function shortText(string $text, int $point): array
    {
        $textArray = explode(' ', $text);
        $countChar = iconv_strlen($text);

        $result = [];
        $text = $textArray[0] . ' ';
        unset($textArray[0]);
        $count = count($textArray);

        for ($i = 1; $i < $count; $i++) {

            if (iconv_strlen(trim($text)) + iconv_strlen(trim($textArray[$i])) > ceil($countChar / $point)) {
                $result[] = trim($text);
                $text = '';

                if ($i + 1 == $count) {
                    break;
                }
            }

            $text .= $textArray[$i] . ' ';
            unset($textArray[$i]);
        }

        if ($point >= 4) {
            $result[$point - 1] = trim($result[$point - 1] . ' ' . $text . implode(' ', $textArray));
        } else {
            $result[] = $text . implode(' ', $textArray);
        }

        return $result;
    }

    private function formatMilliseconds($milliseconds): string
    {
        $seconds = floor($milliseconds / 1000);
        $minutes = floor($seconds / 60);
        $hours = floor($minutes / 60);
        $milliseconds = $milliseconds % 1000;
        $seconds = $seconds % 60;
        $minutes = $minutes % 60;

        $format = '%u:%02u:%02u.%03u';

        return sprintf($format, $hours, $minutes, $seconds, $milliseconds);
    }
}

