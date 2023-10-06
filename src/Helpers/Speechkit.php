<?php

namespace App\Helpers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class Speechkit
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    /**Генерируем Speechkit*/
    public function generator(string $text, string $fileName): int
    {
        try {
            $byte = mb_strlen($text, '8bit');
            $filePath = DIRECTORY_SPEECHKIT . $fileName;
            $result = false;

            if ($byte <= 1600) {
                $response = $this->client->post('https://tts.api.cloud.yandex.net/speech/v1/tts:synthesize',
                    [
                        'headers' => [
                            'Authorization' => 'Bearer t1.9euelZqLjs-OjIvMk46JmpCPmseei-3rnpWamJiTmpbNnImPnZPPjMbHnJDl8_dhZgRX-e9uZSJf_d3z9yEVAlf5725lIl_9zef1656VmpCQmomZzcrMypuZmZmKm5SU7_zF656VmpCQmomZzcrMypuZmZmKm5SU.jyNwOhBbREoIrIBwyS8xDo6cnKK40GDLm11tv9bieKXsMYjcllOV_8CC7VxQu4aYIT8VskxuxsPy959G41r5Dw',
                            'x-folder-id' => 'b1glckrv5eg7s4kkhtpn',
                            'Content-Type' => 'application/x-www-form-urlencoded'
                        ],
                        'form_params' => [
                            'text' => $text,
                            'format' => 'mp3',
                            'lang' => 'ru-RU',
                            'voice' => 'jane',
                            'emotion' => 'good',
                            'folderId' => 'b1glckrv5eg7s4kkhtpn'
                        ]
                    ]);

                if ($response->getStatusCode() !== 200) {
                    return false;
                }

                $length = file_put_contents($filePath, $response->getBody()->getContents());

                if ($length !== false) {
                    $result = true;
                }

            } else {
                $byte = ceil($byte / 1500);
                $desc = $text . ' ';

                $l = intval(strlen($desc) / $byte + strlen($desc) * 0.02);
                $desc = preg_replace("[\r\n]", " ", $desc);
                preg_match_all("/(.{1,$l})[ \n\r\t]+/", $desc, $descArray);

                $result = $this->SplitMp3($descArray[0], $fileName);
            }
            if ($result) {
                // узнать длину звуковой дорожки
                $sec = shell_exec("sox {$filePath} -n stat 2>&1  | grep Length | awk '{print $3}' | tr -d ',$'");
                $sec = ceil($sec);

                return $sec;
            }
            return false;

        } catch (GuzzleException $e) {
            return false;
        }
    }

    /**
     * @throws GuzzleException
     */
    private function SplitMp3($Mp3Files, $number): bool
    {
        $tmp_array = [];

        foreach ($Mp3Files as $key => $item) {

            $response = $this->client->post('https://tts.api.cloud.yandex.net/speech/v1/tts:synthesize',
                [
                    'headers' => [
                        'Authorization' => 'Bearer t1.9euelZqLjs-OjIvMk46JmpCPmseei-3rnpWamJiTmpbNnImPnZPPjMbHnJDl8_dhZgRX-e9uZSJf_d3z9yEVAlf5725lIl_9zef1656VmpCQmomZzcrMypuZmZmKm5SU7_zF656VmpCQmomZzcrMypuZmZmKm5SU.jyNwOhBbREoIrIBwyS8xDo6cnKK40GDLm11tv9bieKXsMYjcllOV_8CC7VxQu4aYIT8VskxuxsPy959G41r5Dw',
                        'x-folder-id' => 'b1glckrv5eg7s4kkhtpn',
                        'Content-Type' => 'application/x-www-form-urlencoded'
                    ],
                    'form_params' => [
                        'text' => trim($item),
                        'format' => 'mp3',
                        'lang' => 'ru-RU',
                        'voice' => 'jane',
                        'emotion' => 'good',
                        'folderId' => 'b1glckrv5eg7s4kkhtpn'
                    ]
                ]);

            if ($response->getStatusCode() !== 200) {
                return false;
            }

            $length = file_put_contents(DIRECTORY_SPEECHKIT . $number . '_' . $key . '.mp3', $response->getBody()->getContents());
            if ($length == false) {
                return false;
            }

            $tmp_array[] = DIRECTORY_SPEECHKIT . $number . '_' . $key . '.mp3';
        }

        $files = implode('|', $tmp_array);
        $ffmpeg = shell_exec('ffmpeg -i "concat:' . $files . '" -acodec copy ' . DIRECTORY_SPEECHKIT . $number . '.mp3 -hide_banner -loglevel error 2>&1');

        if (!is_null($ffmpeg)) {
            return false;
        }

        return true;
    }
}