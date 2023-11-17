<?php

namespace App\Helpers;

use Exception;
use getID3;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Database\Capsule\Manager as DB;
use Monolog\Logger;

class Speechkit
{
    private Client $client;
    private Logger $log;

    public function __construct($log)
    {
        $this->client = new Client();
        $this->log = $log;
    }

    /**Генерируем Speechkit
     * @throws Exception|GuzzleException
     */
    public function generatorWithSubtitles(string $text, string $fileName, array $voiceSetting): array
    {
        try {
            if ($voiceSetting['delay_between_offers_ms'] > 0) {

                $this->log->info('Задержка между предложениями');
                $resultText = $this->spillSubtitlesOffers($text);
                $data = $this->SplitMp3New($resultText, $fileName, $voiceSetting, $voiceSetting['delay_between_offers_ms']);

            } elseif ($voiceSetting['delay_between_paragraphs_ms'] > 0) {

                $this->log->info('Задержка между абзацами');
                $resultText = $this->spillSubtitlesParagraph($text);
                $data = $this->SplitMp3New($resultText, $fileName, $voiceSetting, $voiceSetting['delay_between_paragraphs_ms']);

            } else {
                $this->log->info('Задержка не требуется');
                $resultText = $this->spillSubtitles($text);
                $this->log->info(json_encode($resultText));
                $data = $this->SplitMp3($resultText, $fileName, $voiceSetting);
            }

            $this->log->info('Получили данные по генерации озвучки и субтитров' . json_encode($data));
            $filesName = $data['files'];
            $result = $data['status'];
            $filePath = DIRECTORY_SPEECHKIT . $fileName . '.mp3';

            if (is_null($filesName)) {
                return ['status' => false, 'command' => 'command'];
            }

            if ($result) {
                // узнать длину звуковой дорожки
                $getID3 = new getID3;
                $file = $getID3->analyze($filePath);
                $seconds = $file['playtime_seconds'];

                if (isset($seconds) && !is_null($filesName)) {
                    foreach ($filesName as $item) {
                        unlink($item);
                    }
                }

                return ['status' => true, 'time' => $file['playtime_seconds'], 'name' => $fileName, 'command' => $data['command']];

            } elseif (!is_null($filesName)) {
                foreach ($filesName as $item) {
                    unlink($item);
                }
            }

            return ['status' => false, 'command' => $data['command']];
        } catch (Exception $e) {
            return ['status' => false, 'message' => $e->getMessage()];
            // throw new Exception($e->getMessage());
        }
    }

    /**
     * Разбиваем текст по абзацам
     */
    private function spillSubtitles(string $text): array
    {
        $desc = $text . ' ';
        $desc = preg_replace("[\r\n]", ' ', $desc);
        $desc = str_replace('\n', '', $desc);
        $textArray = explode('.', $desc);
        $countChar = 250;
        $result = [];

        if (count($textArray) == 1 || (count($textArray) == 2 && empty(trim($textArray[1])))) {
            return ['text' => trim($textArray[0]) . '. ', 'merge' => false];
        }


        $text = trim($textArray[0]) . '. ';
        unset($textArray[0]);
        $count = count($textArray);


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
                $rep = str_replace('. .', '.', trim($text) . '. ');
                $rep = str_replace('! .', '!', $rep);
                $rep = str_replace('? .', '?', $rep);
                $rep = str_replace('..', '.', $rep);
                $rep = str_replace('!.', '!', $rep);
                $result[] = ['text' => str_replace('?.', '?', $rep), 'merge' => false];
            }
        }


        return $result;
    }

    /** Распределение текста по предложениям длинною не более 250 симвволов, не теряя смысловой нагрузки */
    private function spillSubtitlesOffers(string $text): array
    {
        $this->log->info('Форматирование текста по предложениям');
        $desc = trim($text);
        $desc = preg_replace("[\r\n]", " ", $desc);
        $desc = preg_replace("[\n]", " ", $desc);
        $desc = str_replace('\n', '', $desc);
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
//        $this->log->info(json_encode($result, true));
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

        $countChar = 50;
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
//        $this->log->info(json_encode($result, true));
        return $result;
    }

    private function SplitMp3New($Mp3Files, $number, array $voiceSetting, int $delayBetween): array
    {
        try {

            $tmp_array = [];
            $subtitles = [];
            $nameAudio = [];

            $this->log->info('Отправка запросов на синтез');
            foreach ($Mp3Files as $key => $item) {

                $response = $this->response($item['text'], $voiceSetting);
                $length = file_put_contents(DIRECTORY_SPEECHKIT . $number . '_' . $key . '.mp3', $response);

                $getID3 = new getID3;
                $file = $getID3->analyze(DIRECTORY_SPEECHKIT . $number . '_' . $key . '.mp3');
                $seconds = $file['playtime_seconds'];
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
//            $this->log->info('Названия полученныйх видео ' . json_encode($tmp_array, true));
//            $this->log->info('Название файлов на удаление ' . json_encode($nameAudio, true));
//            $this->log->info('Получили массив субтитров ' . json_encode($subtitles, true));
            $voices = implode('|', $tmp_array);

            $this->log->info('Начало склейки аудио с задержкой');
            if ($delayBetween > 0) {
                $arrayLongAudio = [];
                $mergesAudio = [];

                foreach ($nameAudio as $key => $audio) {
                    $audioName = DIRECTORY_SPEECHKIT . $audio['nameAudio'] . '.mp3';
//                    $this->log->info('Название файла ' . $audioName);

                    if ($audio['merge']) {

//                        $this->log->info('Файл является частью для склейки ' . $audioName);
                        $mergesAudio[] = DIRECTORY_SPEECHKIT . $audio['nameAudio'] . '.mp3';
//                        $this->log->info('Масиив с файлами для склейки' . json_encode($mergesAudio, true));

                        if (isset($nameAudio[$key + 1])) {
//                            $this->log->info('Это последний фал для склеки? ' . !$nameAudio[$key + 1]['merge']);
                            if (!$nameAudio[$key + 1]['merge']) {
//                                $this->log->info('Последний файл для склеки');
                                $audioName = DIRECTORY_SPEECHKIT . $audio['nameAudio'] . '_merges.mp3';

                                $ffmpeg = 'ffmpeg -i "concat:' . implode('|', $mergesAudio) . '"  -acodec copy -c:a libmp3lame ' . $audioName;
//                                $this->log->info($ffmpeg);
                                shell_exec($ffmpeg . ' -hide_banner -loglevel error 2>&1');

                                $outputAudio = DIRECTORY_SPEECHKIT . $audio['nameAudio'] . '_merges_long.mp3';
//                                $this->log->info('Добавление файлу паузу в начале');
                                $ffmpegPause = 'ffmpeg -i ' . $audioName . ' -af adelay=' . $delayBetween . ' ' . $outputAudio;
//                                $this->log->info($ffmpegPause);
                                shell_exec($ffmpegPause . ' -hide_banner -loglevel error 2>&1');

                                $mergesAudio = [];
                                $arrayLongAudio[] = $outputAudio;
                            }
//                            $this->log->info('Это не последний файлм для склеки');
                        } else {
//                            $this->log->info('Последние файлы для склеки' . json_encode($mergesAudio, true));

                            $audioName = DIRECTORY_SPEECHKIT . $audio['nameAudio'] . '_merges.mp3';
                            $ffmpeg = 'ffmpeg -i "concat:' . implode('|', $mergesAudio) . '"  -acodec copy -c:a libmp3lame ' . $audioName;
//                            $this->log->info($ffmpeg);
                            shell_exec($ffmpeg . ' -hide_banner -loglevel error 2>&1');

                            $outputAudio = DIRECTORY_SPEECHKIT . $audio['nameAudio'] . '_merges_long.mp3';
//                            $this->log->info('Добавление файлу паузу в начале');
                            $ffmpegPause = 'ffmpeg -i ' . $audioName . ' -af adelay=' . $delayBetween . ' ' . $outputAudio;
//                            $this->log->info($ffmpegPause);
                            shell_exec($ffmpegPause . ' -hide_banner -loglevel error 2>&1');

                            $mergesAudio = [];
                            $arrayLongAudio[] = $outputAudio;
                        }

                        continue;
                    }

//                    $this->log->info('Формирование аудио с пустотой спереди');
                    $outputAudio = $audio['nameAudio'] . '_long.mp3';
                    $ffmpeg = 'ffmpeg -i ' . $audioName . ' -af adelay=' . $delayBetween . ' ' . DIRECTORY_SPEECHKIT . $outputAudio;
//                    $this->log->info($ffmpeg);
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
//            $this->log->info($ffmpegShortAudio);
            shell_exec($ffmpegShortAudio . ' -hide_banner -loglevel error 2>&1');
            $tmp_array[] = DIRECTORY_SPEECHKIT . $cutFrontVideo . '.mp3';

//            $this->log->info('Добавлям в конец файла две секунды');
            $ffmpegShortAudioResult = 'ffmpeg -i ' . DIRECTORY_SPEECHKIT . $cutFrontVideo . '.mp3 -af "apad=pad_dur=2" -y ' . DIRECTORY_SPEECHKIT . $number . '.mp3';
//            $this->log->info($ffmpegShortAudioResult);
            shell_exec($ffmpegShortAudioResult . ' -hide_banner -loglevel error 2>&1');


            $this->log->info('Генерируем файл субтитрв');
            /**для субтитров*/
            file_put_contents(DIRECTORY_TEXT . $number . '.srt', $this->getFilesSrt($this->mergesSubtitles($subtitles), $delayBetween));

            $this->log->info('Преабразуем файл субтитров в формат ass');
            $ffmpeg = 'ffmpeg -i ' . DIRECTORY_TEXT . $number . '.srt -y ' . DIRECTORY_TEXT . $number . '.ass';
            shell_exec($ffmpeg . ' -hide_banner -loglevel error 2>&1');

            return ['status' => true, 'files' => $tmp_array, 'command' => $ffmpeg];
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @throws Exception|GuzzleException
     */
    private function SplitMp3($Mp3Files, $number, $voiceSetting): array
    {
        try {
            $delayBetweenParagraphMs = $voiceSetting['delay_between_paragraphs'] ?? 0;

            $tmp_array = [];
            $subtitles = [];
            $nameAudio = [];
            $this->log->error($delayBetweenParagraphMs);
            foreach ($Mp3Files as $key => $item) {

                $response = $this->response($item['text'], $voiceSetting);
                $length = file_put_contents(DIRECTORY_SPEECHKIT . $number . '_' . $key . '.mp3', $response);
                $getID3 = new getID3;
                $file = $getID3->analyze(DIRECTORY_SPEECHKIT . $number . '_' . $key . '.mp3');
                $seconds = $file['playtime_seconds'];

                $subtitles[] = [
                    'text' => $item['text'],
                    'time' => $seconds * 1000,
                ];

                if (!$length) {
                    return ['status' => false, 'files' => []];
                }

                $tmp_array[] = DIRECTORY_SPEECHKIT . $number . '_' . $key . '.mp3';
                $nameAudio[] = $number . '_' . $key;
            }


            $voices = implode('|', $tmp_array);

            if ($delayBetweenParagraphMs > 0) {
                $arrayLongAudio = [];

                foreach ($nameAudio as $key => $audio) {
                    if ($key == 0) {
                        $arrayLongAudio[] = DIRECTORY_SPEECHKIT . $audio . '.mp3';
                        continue;
                    }
                    $outputAudio = $audio . '_long.mp3';
                    $ffmpeg = 'ffmpeg -i ' . DIRECTORY_SPEECHKIT . $audio . '.mp3 -af adelay=' . $delayBetweenParagraphMs . ' ' . DIRECTORY_SPEECHKIT . $outputAudio;
                    $arrayLongAudio[] = DIRECTORY_SPEECHKIT . $outputAudio;
                    $e = shell_exec($ffmpeg . ' -hide_banner -loglevel error 2>&1');
                }

                $tmp_array = array_merge($tmp_array, $arrayLongAudio);
                $voices = implode('|', $arrayLongAudio);
            }


            $ffmpeg = 'ffmpeg -i "concat:' . $voices . '"  -acodec copy -c:a libmp3lame ' . DIRECTORY_SPEECHKIT . $number . '.mp3';
            $errors = shell_exec($ffmpeg . ' -hide_banner -loglevel error 2>&1');
            /**для субтитров*/
            $length = file_put_contents(DIRECTORY_TEXT . $number . '.srt', $this->getFilesSrt($subtitles, $delayBetweenParagraphMs));

            $ffmpeg = 'ffmpeg -i ' . DIRECTORY_TEXT . $number . '.srt -y ' . DIRECTORY_TEXT . $number . '.ass';
            $errors = shell_exec($ffmpeg . ' -hide_banner -loglevel error 2>&1');
            return ['status' => true, 'files' => $tmp_array, 'command' => $ffmpeg];
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    private function getFilesSrt(array $text, float $delayBetweenOffersMs): string
    {
        $arr = [];
        $allTime = 0;
        $counter = 0;
//        $this->log->info('Массив субтитры ' . json_encode($text, true));
        foreach ($text as $key => $item) {

            if ($item['time'] > 5600) {
                $counter += 1;
                $textShort = $this->shortText($item['text']);
                $shortTime = round($item['time'] / 2, 2);

                if ($key == 0) {
                    $arr[] = ($counter) . "\r\n" . '00:00:00,000 --> '
                        . str_replace('.', ',', $this->formatMilliseconds($shortTime + $allTime)) . "\r\n" . $textShort[0] . "\r\n";
                } else {
                    $arr[] = ($counter) . "\r\n" . str_replace('.', ',', $this->formatMilliseconds($allTime))
                        . ' --> ' . str_replace('.', ',', $this->formatMilliseconds($shortTime + $allTime)) . "\r\n" . $textShort[0] . "\r\n";
                }
                $allTimeWhithShort = $shortTime + $allTime;
                $counter += 1;
                $arr[] = ($counter) . "\r\n" . str_replace('.', ',', $this->formatMilliseconds($allTimeWhithShort))
                    . ' --> ' . str_replace('.', ',', $this->formatMilliseconds($item['time'] + $allTime)) . "\r\n" . $textShort[1] . "\r\n";

            } else {
                $counter += 1;
                if ($key == 0) {
                    $arr[] = ($key + 1) . "\r\n" . '00:00:00,000 --> '
                        . str_replace('.', ',', $this->formatMilliseconds($item['time'] + $allTime)) . "\r\n" . $item['text'] . "\r\n";
                } else {
                    $arr[] = ($counter) . "\r\n" . str_replace('.', ',', $this->formatMilliseconds($allTime))
                        . ' --> ' . str_replace('.', ',', $this->formatMilliseconds($item['time'] + $allTime)) . "\r\n" . $item['text'] . "\r\n";
                }
            }

            $allTime = $item['time'] + $allTime + $delayBetweenOffersMs;
        }
        return implode("\r\n", $arr);
    }

    private function shortText(string $text): array
    {
        $textArray = explode(' ', $text);
        $countChar = iconv_strlen($text);
        $result = [];
        $text = $textArray[0] . ' ';
        unset($textArray[0]);
        $count = count($textArray);

        for ($i = 1; $i < $count; $i++) {
            if (iconv_strlen($text) + iconv_strlen($textArray[$i]) > $countChar / 2) {
                $result[] = $text;
                $result[] = implode(" ", $textArray);
                break;
            }

            $text .= $textArray[$i] . ' ';
            unset($textArray[$i]);
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

    /**
     * @throws GuzzleException
     * @throws Exception
     */
    private function response(string $text, array $voiceSetting): bool|string
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
}