<?php

namespace App\Controller\test;

use App\Controller\UserController;
use App\Helpers\CheckTokenExpiration;
use App\Models\User;
use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;

class TestController extends UserController
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function action(): ResponseInterface
    {
        $data = json_decode($this->request->getBody()->getContents(), true);
        $access_token = $this->request->getHeaderLine('token');

//        if (CheckTokenExpiration::action($this->container->get('jwt-secret'), $access_token)) {
        $row = [
            'images' => '1.jpg,2.jpg,3.jpg,5.jpg,6.jpg,7.jpg,8.jpg,9.jpg,10.jpg,',
            'sound_name' => 'test.mp3',
            'number' => 'first',
            'sound_time' => 40,
        ];

        /** Слайдшоу с музыкой */
//        $ffmpeg = $this->getSlideShowCode($row['images'], $row['sound_name'], $row['number'], $row['sound_time']);
//        $errors = shell_exec($ffmpeg . ' -hide_banner -loglevel error 2>&1');

        /** Добавили фон */
//        $ffmpeg = 'ffmpeg -i ' . DIRECTORY_VIDEO . $row['number'] . '.mp4 -i ' . DIRECTORY_MAIN_IMG . 'fon.png -filter_complex "[0:v][1:v]overlay=0:0" -codec:a copy -y '. DIRECTORY_VIDEO . $row['number'] . '1.mp4';

        /** Добавили логотип */
        $ffmpeg = 'ffmpeg -i ' . DIRECTORY_VIDEO . $row['number'] . '1.mp4 -i ' . DIRECTORY_MAIN_IMG . 'logo.png -filter_complex "[1:v][0:v]scale2ref=(450/142)*ih/14/sar:ih/14[wm][base];[base][wm]overlay=main_w-overlay_w-10:10:format=rgb" -pix_fmt yuv420p -c:a copy -y ' . DIRECTORY_VIDEO . $row['number'] . '2.mp4';
//        $errors = shell_exec($ffmpeg . ' -hide_banner -loglevel error 2>&1');

        /** Добавили текст */
//            try {
        var_dump($ffmpeg);

//            } catch (Exception $e) {
//                return $this->respondWithError($e->getCode(), $e->getMessage());
//            }
//        }

//        return $this->respondWithError(215);
    }

    function getPreviewCode($images, $number, $str)
    {
        $i = rand(1, 2);

        if ($i == 1) {
            $rgba = 'rgba(255, 0, 0, 0.9)';
            $white = "white";
        } else if ($i == 2) {
            $rgba = 'rgba(254, 224, 70, 0.9)';
            $white = "black";
        } else {
            $rgba = 'rgba(20, 51, 204, 0.9)';
            $white = "white";
        }

        $images = explode(',', $images);
        $str = mb_strtoupper($str);
        $str = str_replace(['«', '»', '"', ',', '.', '?', '!', ' '], ' ', $str);
        $str = explode(' ', $str);
        $str = getArrayStr($str, 30);
        $str = explode(" ", $str[0]);
        $str = getArrayStr($str, 5);
        $str = implode(' \n', $str);

        $str = "convert -background none -undercolor '" . $rgba . "' -kerning -5 -interline-spacing -4 -pointsize 105 -fill " . $white . " -annotate +50+150 '" .
            $str . ' ' . "' images/foto_" . $images[0] . " images/thumbnail_" . $number . ".jpg";
        return $str;
    }

    private function getSlideShowCode(string $images, string $sound_name, string $number, string $sound_time)
    {
        #каждые 10 секунд меняем фотогрфию
        $count_images = ceil($sound_time / 5);
        $arr_images = explode(',', $images);

//        $tmp = array_merge($arr_images, $arr_images, $arr_images, $arr_images, $arr_images,
//            $arr_images, $arr_images, $arr_images, $arr_images, $arr_images);

        for ($i = 0; $count_images > count($arr_images); $i++) {
            $arr_images[] = $arr_images[$i];
        }
        for ($i = count($arr_images); $count_images < count($arr_images); $i--) {
            unset($arr_images[$i]);
        }

        $imagesString = implode(',', $arr_images);
        $images = ' -i ' . DIRECTORY_IMG . str_replace(',', ' -i ' . DIRECTORY_IMG, $imagesString) . ' ';

        $sound = '-i ' . DIRECTORY_MUSIC . $sound_name . ' ';

        $d = ceil((intval($sound_time) / count($arr_images)) * 25);
        $scale = '';
        $v = '';

        for ($i = 0; $i < count($arr_images); $i++) {
            $scale .= "[{$i}:v]scale=-1:10*ih,zoompan=z='min(zoom+0.0010,1.5)':d={$d}:x='iw/2-(iw/zoom/2)':y='ih/2-(ih/zoom/2)'[v{$i}];";
            $v .= "[v{$i}]";
        }

        $v = $v . 'concat=n=' . count($arr_images) . ':v=1:a=0,format=yuv422p[v]" -map "[v]" -map ' . count($arr_images) . ':a -shortest -y ' . DIRECTORY_VIDEO . $number . '.mp4';
        return 'ffmpeg' . $images . $sound . '-filter_complex "' . $scale . $v;
    }
}