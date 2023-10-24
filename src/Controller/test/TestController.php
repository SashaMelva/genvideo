<?php

namespace App\Controller\test;

use App\Controller\UserController;
use App\Helpers\CheckTokenExpiration;
use App\Helpers\GeneratorFiles;
use App\Helpers\Speechkit;
use App\Helpers\UploadFile;
use App\Models\ColorBackground;
use App\Models\ContentVideo;
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
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;

class TestController extends UserController
{

    private Client $client;

    public function action(): ResponseInterface
    {
        $stringDirectory = str_replace('\\', '\\\\', DIRECTORY_TEXT);
        $stringDirectory = str_replace(':', '\\:', $stringDirectory);
        $ffmpeg = 'ffmpeg -i ' . DIRECTORY_VIDEO  . 'voice.mp4 -filter_complex "subtitles=\'' . $stringDirectory . 'resnew.ass' . '\':force_style=' .
            "'OutlineColour=&H80000000,BorderStyle=3,Outline=1,Shadow=0,MarginV=110,scale=9/16*ih:-1,crop=iw:iw*16/9'" .
            '" map 0:0 -map 0:1 -map 1:0 -c:v h264_nvenc -c:a copy -y ' . DIRECTORY_VIDEO . '4.mp4';
        var_dump($ffmpeg);
        exit();

        'ffmpeg -i D:\OpenServer\domains\genvideo.loc\public\video\фон2.mp4 -vf "crop=((9*in_h)/16):in_h:in_w/2-((9*in_h)/16)/2:0" -y D:\OpenServer\domains\genvideo.loc\public\video\FornatVideo.mp4';
        $text = 'В последние годы медитация стала популярной практикой, и не зря. Она не только приносит много пользы здоровью, но и позволяет ощутить внутренний мир и спокойствие в нашей напряженной жизни. Но если вы новичок в мире медитации, то, возможно, задаетесь вопросом, как начать. Вот несколько советов, которые помогут вам начать свой путь в медитации:

1. Найдите тихое и удобное место
Чтобы получить максимальную пользу от медитации, важно найти место, где можно расслабиться и сосредоточиться, не отвлекаясь на посторонние дела. Это может быть свободная комната, уютный уголок в доме или даже парк или открытое пространство. Позаботьтесь о том, чтобы в этом месте не было беспорядка и можно было удобно расположиться, например, на подушке или стуле.

2. Установите таймер
Одна из самых распространенных проблем для начинающих - следить за временем во время медитации. Чтобы постоянно не сверяться с часами, установите таймер на желаемую продолжительность медитации. Это позволит вам полностью сосредоточиться на практике, не отвлекаясь ни на что.

3. Сосредоточьтесь на дыхании
Простой способ начать медитацию - сосредоточиться на своем дыхании. Закройте глаза и сделайте медленный, глубокий вдох и выдох. Обратите внимание на то, как воздух наполняет легкие, и на ощущение расслабления при выдохе. Это поможет переключить внимание на текущий момент и успокоить мысли.

4. Отбросьте ожидания
Приступая к медитации, важно отбросить все свои ожидания и суждения. Не беспокойтесь, если ваш ум блуждает или если вы не можете полностью успокоить свои мысли. Главное - наблюдать за своими мыслями и пропускать их через себя, не зацикливаясь на них.

5. Будьте последовательны
Как и в любой другой новой привычке, в практике медитации важна последовательность. Выделите для медитации определенное время каждый день, будь то утром или перед сном. При регулярной практике вы начнете замечать преимущества медитации в своей повседневной жизни.
Найдя тихое место, установив таймер, сосредоточившись на дыхании, отбросив ожидания и будучи
последовательным, вы сможете легко начать свой путь к медитации и пожинать плоды для своего здоровья и красоты qqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqq.
Медитация - это мощный инструмент для улучшения физического и психического состояния. Найдя тихое место, установив таймер, сосредоточившись на дыхании, отбросив ожидания и будучи последовательным, вы сможете легко начать свой путь к медитации и пожинать плоды для своего здоровья и красоты. Счастливой медитации!';

        $voiceSetting = [
            'format' => 'mp3',
            'lang' => 'ru-RU',
            'voice' => 'alena',
            'emotion' => 'good',
        ];

//        $fileName = 'res';
        $byte = mb_strlen($text, '8bit');
        $byte = ceil($byte / 250);
        $desc = $text;
        $desc = preg_replace("[\r\n]", " ", $desc);
//
        $length = intval(strlen($desc) / $byte + strlen($desc) * 0.02);
//        $res = explode('.', $desc);
//
//        $resultArray = [];
//        var_dump($length);

        $textArray = explode('.', $desc);
        $countChar = 250;
        $result = [];
        $text = trim($textArray[0]) . '.';
        unset($textArray[0]);
        $count = count($textArray);

        for ($i = 1; $i <= $count; $i++) {

            if (iconv_strlen(trim($text)) + iconv_strlen(trim($textArray[$i])) > $countChar) {
                if (iconv_strlen(trim($textArray[$i])) > $countChar) {

                    $textLongArray = explode(',', trim($textArray[$i]));
                    $textLong = trim($textLongArray[0]) . ',';
                    unset($textLongArray[0]);
                    $countLong = count($textLongArray);

                    for ($j = 1; $j <= $countLong; $j++) {
                        if (iconv_strlen($textLong) + iconv_strlen($textLongArray[$j]) > $countChar) {
                            $result[] = trim($textLong) . ',';
                            $textLong = '';
                        }

                        $textLong .= trim($textLongArray[$j]) . ', ';

                        if ($j == $countLong) {
                            $result[] = trim($textLong) . ',';
                        }
                    }
                    $text = '';
                    continue;
                }

                $rep = str_replace('..', '.', trim($text) . '.');
                $rep = str_replace('!.', '.', $rep);
                $result[] = str_replace('?.', '.', $rep);
                $text = '';
            }

            $text .= trim($textArray[$i]) . '. ';

            if ($i == $count) {
                $rep = str_replace('..', '.', trim($text) . '.');
                $rep = str_replace('!.', '!', $rep);
                $result[] = str_replace('?.', '?', $rep);
            }
        }


        var_dump($result);
        exit();
        $data = $this->SplitMp3($descArray[0], $fileName, $voiceSetting);

        var_dump($data);
        $filesName = $data['files'];
        $result = $data['status'];
        $filePath = DIRECTORY_SPEECHKIT . $fileName . '.mp3';


//        $videoId = 90;
//
//        $voiceData['time'] = '155.92800369288';
//        $fileNameVoice = '99_99';
//        $textData['status'] = true;
//        $textData['name'] = '99_99';
//        $video['text'] = 'В последние годы медитация стала популярной практикой, и не зря. Она не только приносит много пользы здоровью, но и позволяет ощутить внутренний мир и спокойствие в нашей напряженной жизни. Но если вы новичок в мире медитации, то, возможно, задаетесь вопросом, как начать. Вот несколько советов, которые помогут вам начать свой путь в медитации:
//
//1. Найдите тихое и удобное место
//Чтобы получить максимальную пользу от медитации, важно найти место, где можно расслабиться и сосредоточиться, не отвлекаясь на посторонние дела. Это может быть свободная комната, уютный уголок в доме или даже парк или открытое пространство. Позаботьтесь о том, чтобы в этом месте не было беспорядка и можно было удобно расположиться, например, на подушке или стуле.
//
//2. Установите таймер
//Одна из самых распространенных проблем для начинающих - следить за временем во время медитации. Чтобы постоянно не сверяться с часами, установите таймер на желаемую продолжительность медитации. Это позволит вам полностью сосредоточиться на практике, не отвлекаясь ни на что.
//
//3. Сосредоточьтесь на дыхании
//Простой способ начать медитацию - сосредоточиться на своем дыхании. Закройте глаза и сделайте медленный, глубокий вдох и выдох. Обратите внимание на то, как воздух наполняет легкие, и на ощущение расслабления при выдохе. Это поможет переключить внимание на текущий момент и успокоить мысли.
//
//4. Отбросьте ожидания
//Приступая к медитации, важно отбросить все свои ожидания и суждения. Не беспокойтесь, если ваш ум блуждает или если вы не можете полностью успокоить свои мысли. Главное - наблюдать за своими мыслями и пропускать их через себя, не зацикливаясь на них.
//
//5. Будьте последовательны
//Как и в любой другой новой привычке, в практике медитации важна последовательность. Выделите для медитации определенное время каждый день, будь то утром или перед сном. При регулярной практике вы начнете замечать преимущества медитации в своей повседневной жизни.
//
//Медитация - это мощный инструмент для улучшения физического и психического состояния. Найдя тихое место, установив таймер, сосредоточившись на дыхании, отбросив ожидания и будучи последовательным, вы сможете легко начать свой путь к медитации и пожинать плоды для своего здоровья и красоты. Счастливой медитации!';
//            $textData = $this->generatorTextForTitre($video['text'], 99, $voiceData['time']);


    }

    private function response(string $text, array $voiceSetting)
    {
        try {
            $tokenData = DB::table('token_yandex')->where([['id', '=', 1]])->get()->toArray()[0];
            $token = trim($tokenData->token);

            $client = new Client();
            $response = $client->post('https://tts.api.cloud.yandex.net/speech/v1/tts:synthesize',
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

    private function SplitMp3($Mp3Files, $number, $voiceSetting): array
    {
        try {
            $tmp_array = [];
            $subtitles = [];

//            foreach ($Mp3Files as $key => $item) {
//
//                $response = $this->response($item, $voiceSetting);
//                $length = file_put_contents(DIRECTORY_SPEECHKIT . $number . '_' . $key . '.mp3', $response);
//
//                $getID3 = new getID3;
//                $file = $getID3->analyze(DIRECTORY_SPEECHKIT . $number . '_' . $key . '.mp3');
//                $seconds = $file['playtime_seconds'];
//
//                $subtitles[] = [
//                    'text' => $item,
//                    'time' => $seconds * 1000
//                ];
//
//                if (!$length) {
//                    return ['status' => false, 'files' => []];
//                }
//
//                $tmp_array[] = DIRECTORY_SPEECHKIT . $number . '_' . $key . '.mp3';
//            }

            $subtitles = [
                0 => [
                    "text" => "В последние годы медитация стала популярной практикой, и не зря. Она не только приносит много пользы здоровью, но и позволяет ощутить внутренний мир и спокойствие в нашей ",
                    "time" => 11256.0
                ],
                1 => [
                    "text" => "напряженной жизни. Но если вы новичок в мире медитации, то, возможно, задаетесь вопросом, как начать. Вот несколько советов, которые помогут вам начать свой путь в медитации: 1. ",
                    "time" => 12384
                ],
                2 => [
                    "text" => "Найдите тихое и удобное место Чтобы получить максимальную пользу от медитации, важно найти место, где можно расслабиться и сосредоточиться, не отвлекаясь на посторонние дела. ",
                    "time" => 11448
                ],
                3 => [
                    "text" => "Это может быть свободная комната, уютный уголок в доме или даже парк или открытое пространство. Позаботьтесь о том, чтобы в этом месте не было беспорядка и можно было удобно ",
                    "time" => 11736
                ],
                4 => [
                    "text" => "расположиться, например, на подушке или стуле. 2. Установите таймер Одна из самых распространенных проблем для начинающих - следить за временем во время медитации. Чтобы ",
                    "time" => 12384
                ],
                5 => [
                    "text" => "постоянно не сверяться с часами, установите таймер на желаемую продолжительность медитации. Это позволит вам полностью сосредоточиться на практике, не отвлекаясь ни на что. 3. ",
                    "time" => 11352
                ],
                6 => [
                    "text" => "Сосредоточьтесь на дыхании Простой способ начать медитацию - сосредоточиться на своем дыхании. Закройте глаза и сделайте медленный, глубокий вдох и выдох. Обратите внимание на ",
                    "time" => 13008
                ],
                7 => [
                    "text" => "то, как воздух наполняет легкие, и на ощущение расслабления при выдохе. Это поможет переключить внимание на текущий момент и успокоить мысли. 4. Отбросьте ожидания Приступая к ",
                    "time" => 12888
                ],
                8 => [
                    "text" => "медитации, важно отбросить все свои ожидания и суждения. Не беспокойтесь, если ваш ум блуждает или если вы не можете полностью успокоить свои мысли. Главное - наблюдать за ",
                    "time" => 12120
                ],
                9 => [
                    "text" => "своими мыслями и пропускать их через себя, не зацикливаясь на них. 5. Будьте последовательны Как и в любой другой новой привычке, в практике медитации важна последовательность. ",
                    "time" => 11904
                ],
                10 => [
                    "text" => "Выделите для медитации определенное время каждый день, будь то утром или перед сном. При регулярной практике вы начнете замечать преимущества медитации в своей повседневной ",
                    "time" => 11904
                ],
                11 => [
                    "text" => "жизни. Медитация - это мощный инструмент для улучшения физического и психического состояния. Найдя тихое место, установив таймер, сосредоточившись на дыхании, отбросив ",
                    "time" => 12480
                ],
                12 => [
                    "text" => "ожидания и будучи последовательным, вы сможете легко начать свой путь к медитации и пожинать плоды для своего здоровья и красоты. Счастливой медитации! ",
                    "time" => 10320
                ]
            ];
            //var_dump($subtitles);

            $voices = implode('|', $tmp_array);

            $ffmpeg = 'ffmpeg -i "concat:' . $voices . '" -acodec copy -c:a libmp3lame ' . DIRECTORY_SPEECHKIT . $number . '.mp3';
            //var_dump($ffmpeg);
            $errors = shell_exec($ffmpeg . ' -hide_banner -loglevel error 2>&1');

            $length = file_put_contents(DIRECTORY_TEXT . $number . '.srt', $this->getFilesSrt($subtitles));


            $ffmpeg = 'ffmpeg -i ' . DIRECTORY_TEXT . $number . '.srt -y ' . DIRECTORY_TEXT . $number . 'new.ass';
            var_dump($ffmpeg);

            $errors = shell_exec($ffmpeg . ' -hide_banner -loglevel error 2>&1');

            return ['status' => true, 'files' => $tmp_array, 'command' => $ffmpeg];
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }


    private function getFilesSrt(array $text): string
    {
        $arr = [];
        $allTime = 0;
        $counter = 0;
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
                    $arr[] = ($counter) . "\r\n" . str_replace('.', ',', $this->formatMilliseconds($allTime))
                        . ' --> ' . str_replace('.', ',', $this->formatMilliseconds($item['time'] + $allTime)) . "\r\n" . $item['text'] . "\r\n";
                } else {
                    $arr[] = ($key + 1) . "\r\n" . '00:00:00,000 --> '
                        . str_replace('.', ',', $this->formatMilliseconds($item['time'])) . "\r\n" . $item['text'] . "\r\n";
                }
            }

            $allTime = $item['time'] + $allTime;
        }
        return implode("\r\n", $arr);
    }

    private function shortText($text): array
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

//    public function generatorTextForTitre(string $text, int $text_id, string $timeVoice): array
//    {
//        $nameFiles = $text_id . '_4sec';
//        $data = [
//            'name' => $nameFiles,
//            'path' => RELATIVE_PATH_TEXT . $nameFiles,
//            'status' => false
//        ];
//
//        $text = str_replace(' ', ' ', $text);
//
//
//        $countCharAll = floor(iconv_strlen($text) / floor($timeVoice / 4));
//        var_dump(strlen($text));
//        var_dump(iconv_strlen($text));
//        var_dump($timeVoice);
//        var_dump(floor($timeVoice / 5));
//        var_dump(floor(iconv_strlen($text) / floor($timeVoice / 4)));
//        var_dump(ceil(mb_strlen($text, '8bit') / 250));
//        $byte = ceil(mb_strlen($text, '8bit') / 250);
//        $desc = $text . ' ';
//        $l = intval(strlen($desc) / $byte + strlen($desc) * 0.02);
//        $desc = preg_replace("[\r\n]", " ", $desc);
//        preg_match_all("/(.{1,$l})[ \n\r\t]+/", $desc, $descArray);
//        var_dump($descArray[0]);
//        exit();
//
//        $textArray = explode(' ', $text);
//
//        // разбиваем текст на строки по ~ 150 символов
//        $shortTextArray = $this->getArrayStr($textArray, $countCharAll);
//
//        // формируем, сохраняем файл субтитров .srt и конвертируем в .ass
//        $length = file_put_contents(DIRECTORY_TEXT . $nameFiles . '.srt', $this->getFilesSrt($shortTextArray));
//
//        if ($length !== false) {
//            $ffmpeg = 'ffmpeg -i ' . DIRECTORY_TEXT . $nameFiles . '.srt -y ' . DIRECTORY_TEXT . $nameFiles . '.ass';
//        }
//
//        var_dump($ffmpeg);
//        $errors = shell_exec($ffmpeg . ' -hide_banner -loglevel error 2>&1');
//
//        if (is_null($errors)) {
//            $data['status'] = true;
//        }
//
//        return $data;
//    }

//    private function getArrayStr(array $textArray, int $countChar): array
//    {
//        $result = [];
//        $text = $textArray[0] . ' ';
//        $count = count($textArray);
//
//        for ($i = 1; $i < $count; $i++) {
//            if (iconv_strlen($text) + iconv_strlen($textArray[$i]) > $countChar) {
//                $result[] = $text;
//                $text = '';
//            }
//
//            $text .= $textArray[$i] . ' ';
//        }
//
//        $result[] = $text;
//        return $result;
//    }
//    function getArrayStr($shorttext, $count_str, $str = ''): array
//    {
//
//        $subtitles = [];
//        $count = count($shorttext) - 1;
//
//        foreach ($shorttext as $key =>$item) {
//            $str .= $item.' ';
//
//            if (iconv_strlen($str) >= $count_str) {
//                $subtitles[] = trim(str_replace('  ', ' ', $str));
//                $str = '';
//            }
//
//            if (($key == $count) && (iconv_strlen($str) > 0))
//                $subtitles[] = trim(str_replace('  ', ' ', $str));
//        }
//        return $subtitles;
//    }
//
//    private function getFilesSrt($shorttext, $arr = [], $ms = 3999): string
//    {
//        foreach ($shorttext as $key => $item) {
//
//            if ($key == 0) {
//                $arr[] = ($key + 1) . "\r\n" . '00:00:00,000 --> '
//                    . str_replace('.', ',', $this->formatMilliseconds($ms)) . "\r\n" . $item . "\r\n";
//                continue;
//            }
//
//            $arr[] = ($key + 1) . "\r\n" . str_replace('.', ',', $this->formatMilliseconds($ms))
//                . ' --> ' . str_replace('.', ',', $this->formatMilliseconds($ms + 4999)) . "\r\n" . $item . "\r\n";
//            $ms = $ms + 4999;
//        }
//        return implode("\r\n", $arr);
//    }

    private
    function formatMilliseconds($milliseconds): string
    {
        $seconds = floor($milliseconds / 1000);
        $minutes = floor($seconds / 60);
        $hours = floor($minutes / 60);
        $milliseconds = $milliseconds % 1000;
        $seconds = $seconds % 60;
        $minutes = $minutes % 60;

        $format = '%u:%02u:%02u.%03u';
        $time = sprintf($format, $hours, $minutes, $seconds, $milliseconds);
        return rtrim($time, '0');
    }
}

