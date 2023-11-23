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

        $textPreview = 'Добро пожаловать в нашу медитацию перед сном, посвященную ощущению гармонии внутри и вокруг себя. Приготовьтесь улечься и расслабиться, закройте глаза и начните глубоко дышать.\n\nДавайте сначала посветим несколько минут на то, чтобы успокоить свой разум и тело. Позвольте себе отпустить все беспокойства и напряжение, чтобы полностью погрузиться в этот момент.\n\nВаше тело становится тяжелым и расслабленным, каждый вдох наполняет вас спокойствием, а каждый выдох уносит с собой все негативные эмоции и мысли. Чувствуйте, как ваше тело становится все более легким и свободным.\n\nТеперь давайте перенесем свое внимание на наше внутреннее состояние. Почувствуйте свое сердце, его ритмичные пульсации. Ваше сердце наполняется любовью и благодарностью. ЧЧувствуйте, как эти чувства распространяются по всему вашему телу, принося гармонию и спокойствие в каждую клеточку, чувствуйте, как эти чувства распространяются по всему вашему телу, принося гармонию и спокойствие в каждую клеточку, чувствуйте, как эти чувства распространяются по всему вашему телу, принося гармонию и спокойствие в каждую клеточку.\n\nПредставьте себе, что вы находитесь в прекрасном месте природы. Возможно, это лес, пляж или горы. Визуализируйте это место с яркими красками и прекрасным ароматом. Чувствуйте, как вы окружены спокойствием и гармонией природы.\n\nПостепенно расширьте свое восприятие на весь мир вокруг вас. Почувствуйте, как вы связаны с ним энергетическими нитями, как ваше сознание расширяется на все живое. Почувствуйте гармонию и взаимосвязь между всеми существами и элементами природы.\n\nТеперь, когда вы чувствуете эту гармонию внутри себя и вокруг себя, позвольте себе ощутить благодарность за все, что вам дарит мир. Благодарите за каждый день, каждый момент, каждое существо, которое когда-либо пересекло ваш путь. Благодарите за возможность быть здесь и сейчас, в этом моменте гармонии.\n\nЧувствуйте, как эта благодарность наполняет вас счастьем и миром. Позвольте себе остаться в этом состоянии гармонии и благодарности, пока вы не заснете.\n\nСпокойной ночи.';

       $this->spillSubtitlesOffers($textPreview);

       exit();
        return $this->respondWithData($data);
    }

    private function spillSubtitlesOffers(string $text): array
    {
        $this->log->info('Форматирование текста по предложениям');
        $desc = trim($text);

        if (str_contains($desc, '\n') == true) {
            $textArrayParagraph = explode('\n', $desc);
        } else {
            $textArrayParagraph = explode("\n", $desc);
        }

        $this->log->info(json_encode($textArrayParagraph, JSON_UNESCAPED_UNICODE));
        $textArray = [];

        foreach ($textArrayParagraph as $paragraph) {
            $textArray = array_merge($textArray,explode('.', $paragraph));
        }

        if (iconv_strlen($textArray[count($textArray) - 1]) >= 0 && iconv_strlen($textArray[count($textArray) - 1]) < 2) {
            unset($textArray[count($textArray) - 1]);
        }

        $countChar = 250;
        $result = [];
        var_dump($textArray);
        /** Проверка остальных предложения на количество символов */
        for ($i = 0; $i < count($textArray); $i++) {

            if (empty($textArray[$i])) {
                continue;
            }

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
        var_dump($result);
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
        $result = [];
        $countChar = iconv_strlen($text);
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

