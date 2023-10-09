<?php

namespace App\Helpers;

use Exception;
use getID3;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

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
    public function generator(string $text, string $fileName, array $voiceSetting)
    {
        try {
            $byte = mb_strlen($text, '8bit');
            $filePath = DIRECTORY_SPEECHKIT . $fileName . '.' . $voiceSetting['format'];
            $result = false;
            $filesName = [];

           #TODO разбить текст по словам, а не по битам
            if ($byte <= 250) {
                $response = $this->response($byte, $voiceSetting);
                $length = file_put_contents($filePath, $response);

                if ($length !== false) {
                    $result = true;
                }

            } else {
                $byte = ceil($byte / 250);
                $desc = $text . ' ';

                $l = intval(strlen($desc) / $byte + strlen($desc) * 0.02);
                $desc = preg_replace("[\r\n]", " ", $desc);
                preg_match_all("/(.{1,$l})[ \n\r\t]+/", $desc, $descArray);

                $data = $this->SplitMp3($descArray[0], $fileName, $voiceSetting);
                $filesName = $data['files'];
                $result = $data['status'];
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
                return $file['playtime_seconds'];

            } elseif (!empty($filesName)) {
                foreach ($filesName as $item) {
                    unlink($item);
                }
            }
            return false;

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
            $tmp_array = [];

            foreach ($Mp3Files as $key => $item) {

                $response = $this->response($item, $voiceSetting);

                $length = file_put_contents(DIRECTORY_SPEECHKIT . $number . '_' . $key . '.mp3', $response);
                if ($length == false) {
                    return ['status' => false, 'files' => []];
                }

                $tmp_array[] = DIRECTORY_SPEECHKIT . $number . '_' . $key . '.mp3';
            }

            $voices = implode('|', $tmp_array);

            $ffmpeg = 'ffmpeg -i "concat:' . $voices . '" -acodec copy -c:a libmp3lame ' . DIRECTORY_SPEECHKIT . $number . '.mp3';
            $errors = shell_exec('-hide_banner -loglevel error 2>&1');

            var_dump($ffmpeg);

            if (!is_null($errors)) {
                return ['status' => false, 'files' => $tmp_array];
            }

            return ['status' => true, 'files' => $tmp_array];
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @throws GuzzleException
     * @throws Exception
     */
    private function response(string $text, array $voiceSetting): bool|string
    {
        try {
            $token = 't1.9euelZqNzJSVl8qPm5OZlMibm5rKi-3rnpWamJiTmpbNnImPnZPPjMbHnJDl8_dvFXlW-e97AFU0_N3z9y9Edlb573sAVTT8zef1656Vms-UzY6WlpqSnsyeiZnGx8yO7_zF656Vms-UzY6WlpqSnsyeiZnGx8yO.MUzEv_5_Ya_jQQKgQRsBvi9YD2p_pf7lGTZCaDU9fl_kXJiXuG6mZMgihAiC-nNR9T0y2RUDm5i4DuaVCJTqBg';
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