<?php

namespace App\Helpers;

class GeneratorFiles
{
    private int $contentId;

    public function __construct($contentId)
    {
        $this->contentId = $contentId;
    }

    /**Генерируем текст для субтитров*/
    public function generatorTextForTitre(string $text): array
    {
        $data = [
            'name' => $this->contentId . '.ass',
            'path' => RELATIVE_PATH_TEXT  . $this->contentId . '.ass',
        ];

        $textArray = explode(' ', $text);

        // разбиваем текст на строки по ~ 150 символов
        $shortTextArray = $this->getArrayStr($textArray, 150);

        // формируем, сохраняем файл субтитров .srt и конвертируем в .ass
        $length = file_put_contents(DIRECTORY_TEXT  . $this->contentId . '.srt', $this->getFilesSrt($shortTextArray));

        if ($length !== false) {
            $ffmpeg = 'ffmpeg -i ' . DIRECTORY_TEXT  . $this->contentId . '.srt -y ' . DIRECTORY_TEXT  . $this->contentId . '.ass';
        }

        $errors = shell_exec($ffmpeg . ' -hide_banner -loglevel error 2>&1');

        if (!is_null($errors)) {
            #TODO
        }

        return $data;
    }

    /**Генерируем субтитры*/
    public function generatorText(): bool
    {
        $stringDirectory = str_replace('\\', '\\\\', DIRECTORY_TEXT);
        $stringDirectory = str_replace(':', '\\:', $stringDirectory);
       
        $ffmpeg = 'ffmpeg -i ' . DIRECTORY_VIDEO . $this->contentId . '_logo.mp4 -filter_complex "subtitles=\'' . $stringDirectory . $this->contentId . '.ass\':force_style=' .
            "'OutlineColour=&H80000000,BorderStyle=3,Outline=1,Shadow=0,MarginV=110'" .
            '" -y ' . DIRECTORY_VIDEO . $this->contentId . '_text.mp4';
        $errors = shell_exec($ffmpeg . ' -hide_banner -loglevel error 2>&1');

        if (!is_null($errors)) {
            return false;
        }

        return true;
    }

    /**Генерируем cлайдшоу с фоновой музыкой*/
    public function generatorSladeShow(array $images, string $sound_name, string $sound_time): bool
    {
        $ffmpeg = $this->getSlideShowCode($images, $sound_name, $this->contentId, $sound_time);
        $errors = shell_exec($ffmpeg . ' -hide_banner -loglevel error 2>&1');

        if (!is_null($errors)) {
            return false;
        }

        return true;
    }

    /**Генерируем фон*/
    public function generatorBackground(string $nameFileBackground): bool
    {
        $ffmpeg = 'ffmpeg -i ' . DIRECTORY_VIDEO . $this->contentId . '.mp4 -i ' . DIRECTORY_MAIN_IMG . $nameFileBackground .' -filter_complex "[0:v][1:v]overlay=0:0" -codec:a copy -y ' . DIRECTORY_VIDEO . $this->contentId . '_fon.mp4';
        $errors = shell_exec($ffmpeg . ' -hide_banner -loglevel error 2>&1');

        if (!is_null($errors)) {
            return false;
        }

        return true;
    }

    /**Генерируем логотип*/
    public function generatorLogo(string $nameFileLogo): bool
    {
        $ffmpeg = 'ffmpeg -i ' . DIRECTORY_VIDEO . $this->contentId . '_fon.mp4 -i ' . DIRECTORY_MAIN_IMG . $nameFileLogo .' -filter_complex "[1:v][0:v]scale2ref=(450/142)*ih/14/sar:ih/14[wm][base];[base][wm]overlay=main_w-overlay_w-10:10:format=rgb" -pix_fmt yuv420p -c:a copy -y ' . DIRECTORY_VIDEO . $this->contentId . '_logo.mp4';
        $errors = shell_exec($ffmpeg . ' -hide_banner -loglevel error 2>&1');

        if (!is_null($errors)) {
            return false;
        }

        return true;
    }


    private function getArrayStr(array $textArray, int $countChar): array
    {
        $result = [];
        $text = $textArray[0] . ' ';
        $count = count($textArray);

        for ($i = 1; $i < $count; $i++) {
            if (strlen($text) + strlen($textArray[$i]) > $countChar) {
                $result[] = $text;
                $text = '';
            }

            $text .= $textArray[$i] . ' ';
        }

        $result[] = $text;
        return $result;
    }

    private function getSlideShowCode(array $arr_images, string $sound_name, string $number, string $sound_time): string
    {
        #каждые 10 секунд меняем фотогрфию
        $count_images = ceil($sound_time / 10);

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

        $v = $v . 'concat=n=' . count($arr_images) . ':v=1:a=0,format=yuv422p[v]" -map "[v]" -map ' . count($arr_images) . ':a -shortest -y video/rambler_' . $number . '.mp4';
        return 'ffmpeg' . $images . $sound . '-filter_complex "' . $scale . $v;
    }

    private function getFilesSrt($shorttext, $arr = [], $ms = 8999): string
    {
        foreach ($shorttext as $key => $item) {

            if ($key == 0) {
                $arr[] = ($key + 1) . '\r\n' . '00:00:00,000 --> '
                    . str_replace('.', ',', $this->formatMilliseconds($ms)) . '\r\n' . $item . '\r\n';
                continue;
            }

            $arr[] = ($key + 1) . '\r\n' . str_replace('.', ',', $this->formatMilliseconds($ms))
                . ' --> ' . str_replace('.', ',', $this->formatMilliseconds($ms + 8999)) . '\r\n' . $item . '\r\n';
            $ms = $ms + 8999;
        }
        return implode('\r\n', $arr);
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
}