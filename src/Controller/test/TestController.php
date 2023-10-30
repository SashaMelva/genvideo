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
        $ffmpeg = 'ffmpeg -re  -auto_convert 1 -safe 0 -f concat -i mylist.txt  -i qr.png -filter_complex "[0:v][1:v]overlay=main_w-overlay_w-15:15"  -c:v libx264 -g 40 -keyint_min 1 -vsync 1 -c:a aac  -ar 48000 -ac 2 -strict -2 -af aresample=async=1000  OUTPUT.mp4';
        var_dump($ffmpeg);
        exit();



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


    private function formatMilliseconds($milliseconds): string
    {
        $seconds = floor($milliseconds / 1000);
        $minutes = floor($seconds / 60);
        $hours = floor($minutes / 60);
        $milliseconds = $milliseconds % 1000;
        $seconds = $seconds % 60;
        $minutes = $minutes % 60;

        $format = '%u:%02u:%02u.%03u';


        $time = sprintf($format, $hours, $minutes, $seconds, $milliseconds);
//        $resultTime = rtrim($time, '0');
//
//        if (empty(explode('.', $resultTime)[1])) {
//            $resultTime .= '000';
//        }

        return $time;
    }
}

