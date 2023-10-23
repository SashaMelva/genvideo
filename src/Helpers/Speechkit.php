<?php

namespace App\Helpers;

use Exception;
use getID3;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Database\Capsule\Manager as DB;

class Speechkit
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    /**Генерируем Speechkit
     * @throws Exception|GuzzleException
     */
    public function generator(string $text, string $fileName, array $voiceSetting): array
    {
        try {
            $byte = mb_strlen($text, '8bit');
            $filePath = DIRECTORY_SPEECHKIT . $fileName . '.' . $voiceSetting['format'];
            $result = false;
            $filesName = [];


            if ($byte <= 250) {
                $response = $this->response($byte, $voiceSetting);
                $length = file_put_contents($filePath, $response);

                if ($length !== false) {
                    $result = true;
                }

            } else {
                $desc = $text . ' ';
                $desc = preg_replace("[\r\n]", " ", $desc);
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

                $data = $this->SplitMp3($result, $fileName, $voiceSetting);
                $filesName = $data['files'];
                $result = $data['status'];
                $filePath = DIRECTORY_SPEECHKIT . $fileName . '.mp3';
            }
            if ($result) {
                // узнать длину звуковой дорожки
                $getID3 = new getID3;
                $file = $getID3->analyze($filePath);
                $seconds = $file['playtime_seconds'];

                if (isset($seconds) && !empty($filesName)) {
                    foreach ($filesName as $item) {
                        unlink($item);
                    }
                }

                return ['status' => true, 'time' => $file['playtime_seconds'], 'command' => $data['command']];

            } elseif (!empty($filesName)) {
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
     * @throws Exception|GuzzleException
     */
    private function SplitMp3($Mp3Files, $number, $voiceSetting): array
    {
        try {
            $tmp_array = [];
            $subtitles = [];

            foreach ($Mp3Files as $key => $item) {

                $response = $this->response($item, $voiceSetting);
                $length = file_put_contents(DIRECTORY_SPEECHKIT . $number . '_' . $key . '.mp3', $response);

                $getID3 = new getID3;
                $file = $getID3->analyze(DIRECTORY_SPEECHKIT . $number . '_' . $key . '.mp3');
                $seconds = $file['playtime_seconds'];

                $subtitles[] = [
                    'text' => $item,
                    'time' => $seconds * 1000,
                ];

                if (!$length) {
                    return ['status' => false, 'files' => []];
                }

                $tmp_array[] = DIRECTORY_SPEECHKIT . $number . '_' . $key . '.mp3';
            }

            $voices = implode('|', $tmp_array);

            $ffmpeg = 'ffmpeg -i "concat:' . $voices . '" -acodec copy -c:a libmp3lame ' . DIRECTORY_SPEECHKIT . $number . '.mp3';
            $errors = shell_exec($ffmpeg . ' -hide_banner -loglevel error 2>&1');

            /**для субтитров*/
            $length = file_put_contents(DIRECTORY_TEXT . $number . '.srt', $this->getFilesSrt($subtitles));

            $ffmpeg = 'ffmpeg -i ' . DIRECTORY_TEXT . $number . '.srt -y ' . DIRECTORY_TEXT . $number . '.ass';
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
        $time = sprintf($format, $hours, $minutes, $seconds, $milliseconds);
        return rtrim($time, '0');
    }
    /**
     * @throws GuzzleException
     * @throws Exception
     */
    private function response(string $text, array $voiceSetting)
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