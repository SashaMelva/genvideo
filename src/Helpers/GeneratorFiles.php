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
    public function generatorTextForTitre(string $text, int $text_id): array
    {
        $nameFiles = $this->contentId . '_' . $text_id;
        $data = [
            'name' => $nameFiles . '.ass',
            'path' => RELATIVE_PATH_TEXT . $nameFiles . '.ass',
            'status' => false
        ];

        $text = str_replace(' ', ' ', $text);
        $textArray = explode(' ', $text);

        // разбиваем текст на строки по ~ 150 символов
        $shortTextArray = $this->getArrayStr($textArray, 150);

        // формируем, сохраняем файл субтитров .srt и конвертируем в .ass
        $length = file_put_contents(DIRECTORY_TEXT . $nameFiles . '.srt', $this->getFilesSrt($shortTextArray));

        var_dump(DIRECTORY_TEXT);
        var_dump($length);
        if ($length !== false) {
            $ffmpeg = 'ffmpeg -i ' . DIRECTORY_TEXT . $nameFiles . '.srt -y ' . DIRECTORY_TEXT . $nameFiles . '.ass';
        }

        var_dump($ffmpeg);
        $errors = shell_exec($ffmpeg . ' -hide_banner -loglevel error 2>&1');

        if (is_null($errors)) {
            $data['status'] = true;
        }

        return $data;
    }

    /**Генерируем субтитры*/
    public function generatorText(string $videoName, string $titerName): array
    {
        $resultName = $this->contentId . '_text';
        $stringDirectory = str_replace('\\', '\\\\', DIRECTORY_TEXT);
        $stringDirectory = str_replace(':', '\\:', $stringDirectory);

        $ffmpeg = 'ffmpeg -i ' . DIRECTORY_VIDEO . $videoName . '.mp4 -filter_complex "subtitles=\'' . $stringDirectory . $titerName . '.ass' . '\':force_style=' .
            "'OutlineColour=&H80000000,BorderStyle=3,Outline=1,Shadow=0,MarginV=110'" .
            '" -y ' . DIRECTORY_VIDEO . $resultName . '.mp4';

        var_dump($ffmpeg);
        $errors = shell_exec($ffmpeg . ' -hide_banner -loglevel error 2>&1');

        if (!is_null($errors)) {
            return ['status' => false];
        }

        return ['fileName' => $resultName, 'status' => true];
    }

    /**Генерируем cлайдшоу с фоновой музыкой*/
    public function generatorSladeShow(array $images, string $sound_name, string $time): array
    {
        $ffmpeg = $this->getSlideShowCode($images, $sound_name, $time);
        var_dump($ffmpeg);
        $errors = shell_exec('sudo ' . $ffmpeg . ' -hide_banner -loglevel error 2>&1');

        if (!is_null($errors)) {
            return ['status' => false];
        }

        return ['fileName' => $this->contentId, 'status' => true];
    }

    /**Генерируем видео с фоновой музыкой*/
    public function generatorBackgroundVideoAndMusic(string $nameVideo, string $sound_name, string $time): array
    {
        $ffmpeg = 'ffmpeg -i ' . DIRECTORY_ADDITIONAL_VIDEO . $nameVideo . ' -i ' . DIRECTORY_MUSIC . $sound_name . ' -c:v copy -c:a aac -map 0:v:0 -map 1:a:0 ' . DIRECTORY_VIDEO . $this->contentId . '_1.mp4';
        var_dump($ffmpeg);
        $errors = shell_exec($ffmpeg . ' -hide_banner -loglevel error 2>&1');

        if (!is_null($errors)) {
            return ['status' => false];
        }

        return ['fileName' => $this->contentId, 'status' => true];
    }

    /**Генерируем фон*/
    public function generatorBackground(string $nameFileBackground, string $videoName): array
    {
        $resultName = $this->contentId . '_fon';
        $ffmpeg = 'ffmpeg -i ' . DIRECTORY_VIDEO . $videoName . '.mp4 -i ' . DIRECTORY_BACKGROUND . $nameFileBackground . ' -filter_complex "[0:v][1:v]overlay=0:0" -codec:a copy -y ' . DIRECTORY_VIDEO . $resultName . '.mp4';
        $errors = shell_exec($ffmpeg . ' -hide_banner -loglevel error 2>&1');

        var_dump($ffmpeg);
        if (!is_null($errors)) {
            return ['status' => false];
        }

        return ['fileName' => $resultName, 'status' => true];
    }

    /**Генерируем логотип*/
    public function generatorLogo(string $nameFileLogo, string $videoName): array
    {
        $resultName = $this->contentId . '_logo';
        $ffmpeg = 'ffmpeg -i ' . DIRECTORY_VIDEO . $videoName . '.mp4 -i ' . DIRECTORY_LOGO_IMG . $nameFileLogo . ' -filter_complex "[1:v][0:v]scale2ref=(450/142)*ih/14/sar:ih/14[wm][base];[base][wm]overlay=main_w-overlay_w-10:10:format=rgb" -pix_fmt yuv420p -c:a copy -y ' . DIRECTORY_VIDEO . $resultName . '.mp4';
        $errors = shell_exec($ffmpeg . ' -hide_banner -loglevel error 2>&1');

        var_dump($ffmpeg);
        if (!is_null($errors)) {
            return ['status' => false];
        }

        return ['fileName' => $resultName, 'status' => true];
    }

    /**Накладываем озвучку на видео*/
    public function generatorMusic(string $nameFileVoice, string $videoName, string $time): array
    {
        $resultName = $this->contentId . '_sound';

        $ffmpeg = 'ffmpeg -y -i ' . DIRECTORY_VIDEO . $videoName . '.mp4 -i ' . DIRECTORY_SPEECHKIT . $nameFileVoice . ' -filter_complex "[0]volume=0.4[a];[1]volume=1.8[b];[a][b]amix=inputs=2:duration=longest" -c:a libmp3lame ' . DIRECTORY_VIDEO . $resultName . '.mp4';
        $errors = shell_exec($ffmpeg . ' -hide_banner -loglevel error 2>&1');

        var_dump($ffmpeg);
        if (!is_null($errors)) {
            return ['status' => false];
        }

        return ['fileName' => $resultName, 'status' => true];
    }


    /**Склеиваем видео*/
    public function mergeVideo(string $nameVideoContent, ?string $nameVideoStart = null, ?string $nameVideoEnd = null): array
    {
        $fileName = $this->contentId . '_result1';
        $ffmpeg = 'ffmpeg -i "concat:';

        if (!is_null($nameVideoStart)) {
            $fileNameStart = str_replace('.mp4', '', $nameVideoStart);
            if ($this->mpegtsFiles($fileNameStart, DIRECTORY_ADDITIONAL_VIDEO)) {
                $ffmpeg .= DIRECTORY_ADDITIONAL_VIDEO . $fileNameStart . '.ts' . '|';
            } else {
                return ['status' => false];
            }
        }

        if ($this->mpegtsFiles($nameVideoContent, DIRECTORY_VIDEO)) {
            $ffmpeg .= DIRECTORY_VIDEO . $nameVideoContent . '.ts';
        } else {
            return ['status' => false];
        }


        if (!is_null($nameVideoEnd)) {
            $fileNameEnd = str_replace('.mp4', '', $nameVideoEnd);
            if ($this->mpegtsFiles($fileNameEnd, DIRECTORY_ADDITIONAL_VIDEO)) {
                $ffmpeg .= '|' . DIRECTORY_ADDITIONAL_VIDEO . $fileNameEnd;
            } else {
                return ['status' => false];
            }
        }

        $ffmpeg .= '" -vcodec copy -acodec copy ' . DIRECTORY_VIDEO . $fileName . '.mp4';
        var_dump($ffmpeg);
        $errors = shell_exec($ffmpeg . ' -hide_banner -loglevel error 2>&1');

        if (!is_null($errors)) {
            return ['status' => false];
        }

        return ['fileName' => $this->contentId, 'status' => true];
    }

    private function mpegtsFiles(string $fileName, string $directory): bool
    {

        $ffmpeg = 'ffmpeg -i ' . $directory . $fileName . '.mp4' . ' -acodec copy -vcodec copy -vbsf h264_mp4toannexb -f mpegts ' . $directory . $fileName . '.ts';
        var_dump($ffmpeg);
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

    private function getSlideShowCode(array $arr_images, string $sound_name, string $sound_time): string
    {
        $number = $this->contentId;
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

        $v = $v . 'concat=n=' . count($arr_images) . ':v=1:a=0,format=yuv422p[v]" -map "[v]" -map ' . count($arr_images) . ':a -shortest -y ' . DIRECTORY_VIDEO . $number . '.mp4';
        return 'ffmpeg' . $images . $sound . '-filter_complex "' . $scale . $v;
    }

    private function getFilesSrt($shorttext, $arr = [], $ms = 4999): string
    {
        foreach ($shorttext as $key => $item) {

            if ($key == 0) {
                $arr[] = ($key + 1) . "\r\n" . '00:00:00,000 --> '
                    . str_replace('.', ',', $this->formatMilliseconds($ms)) . "\r\n" . $item . "\r\n";
                continue;
            }

            $arr[] = ($key + 1) . "\r\n" . str_replace('.', ',', $this->formatMilliseconds($ms))
                . ' --> ' . str_replace('.', ',', $this->formatMilliseconds($ms + 4999)) . "\r\n" . $item . "\r\n";
            $ms = $ms + 4999;
        }
        return implode("\r\n", $arr);
    }

    private function getFilesSrtTest($shorttext, $arr = [], $ms = 8999)
    {

        foreach ($shorttext as $key => $item) {

            if ($key == 0) {
                $arr[] = ($key + 1) . "\r\n" . '00:00:03,099 --> '
                    . str_replace('.', ',', $this->formatMilliseconds($ms + 3099)) . "\r\n" . $item . "\r\n";
                continue;
            }

            $arr[] = ($key + 1) . "\r\n" . str_replace('.', ',', $this->formatMilliseconds($ms + 3099))
                . ' --> ' . str_replace('.', ',', $this->formatMilliseconds($ms + 8999 + 3099)) . "\r\n" . $item . "\r\n";
            $ms = $ms + 8999;
        }
        return implode("\r\n", $arr);
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