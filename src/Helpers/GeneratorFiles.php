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
            'name' => $nameFiles,
            'path' => RELATIVE_PATH_TEXT . $nameFiles,
            'status' => false
        ];

        $text = str_replace(' ', ' ', $text);
        $textArray = explode(' ', $text);

        // разбиваем текст на строки по ~ 150 символов
        $shortTextArray = $this->getArrayStr($textArray, 150);

        // формируем, сохраняем файл субтитров .srt и конвертируем в .ass
        $length = file_put_contents(DIRECTORY_TEXT . $nameFiles . '.srt', $this->getFilesSrt($shortTextArray));

        if ($length !== false) {
            $ffmpeg = 'ffmpeg -i ' . DIRECTORY_TEXT . $nameFiles . '.srt -y ' . DIRECTORY_TEXT . $nameFiles . '.ass';
        }

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

        $errors = shell_exec($ffmpeg . ' -hide_banner -loglevel error 2>&1');

        if (!is_null($errors)) {
            return ['status' => false, 'command' => $ffmpeg];
        }

        unlink(DIRECTORY_VIDEO . $videoName . '.mp4');
        return ['fileName' => $resultName, 'status' => true];
    }

    /**Генерируем cлайдшоу с фоновой музыкой*/
    public function generatorSladeShow(array $images, string $sound_name, string $time): array
    {
        $number = $this->contentId;

            $ffmpeg = $this->getSlideShowCode($images, $sound_name, $time);
//        } else {
//            $timeSlide = ceil((intval($time) / count($images)) * 25);;
//            $i = 1;
//            $v = "[v{$i}]";
//            $nameVideo = [];
//
//            foreach ($images as $key => $image) {
//                $nameVideo[] = $key . '_' . $number;
//                $v = $v . ' concat=n=' . 1 . ':v=1:a=0,format=yuv422p[v]" -map "[v]" -map ' . 1 . ':a -shortest -y ' . DIRECTORY_VIDEO . $key . '_' . $number . '.mp4';
//                $ffmpeg = 'ffmpeg -i ' . DIRECTORY_IMG . $image . ' -filter_complex "' . "[{$i}:v]scale=-1:10*ih,zoompan=z='min(zoom+0.0010,1.5)':d={$timeSlide}:x='iw/2-(iw/zoom/2)':y='ih/2-(ih/zoom/2)'[v{$i}];" . $v;
//                $errors = shell_exec($ffmpeg . ' -hide_banner -loglevel error 2>&1');
//
//                if (!is_null($errors)) {
//                    return ['status' => false, 'command' => $ffmpeg];
//                }
//            }
//
//            $ffmpegMerge = 'ffmpeg -i "concat:';
//            foreach ($nameVideo as $item) {
//                if ($this->mergeFiles($item, DIRECTORY_VIDEO)) {
//                    $ffmpegMerge .= DIRECTORY_VIDEO . $item . '.ts' . '|';
//                } else {
//                    return ['status' => false];
//                }
//            }
//
//            $ffmpegMerge .= '" -vcodec copy -acodec copy ' . DIRECTORY_VIDEO . $number . '.mp4';
//            $errors = shell_exec($ffmpegMerge . ' -hide_banner -loglevel error 2>&1');
//
//            if (!is_null($errors)) {
//                return ['status' => false, 'command' => $ffmpegMerge];
//            }
//
//        }

        $errors = shell_exec($ffmpeg . ' -hide_banner -loglevel error 2>&1');

        if (!is_null($errors)) {
            return ['status' => false, 'command' => $ffmpeg];
        }

        return ['fileName' => $this->contentId, 'status' => true];
    }

    /**Генерируем видео с фоновой музыкой*/
    public function generatorBackgroundVideoAndMusic(string $nameVideo, string $sound_name, string $time): array
    {
        $resultName =  $this->contentId . '_music';
        $ffmpeg = 'ffmpeg -i ' . DIRECTORY_VIDEO . $nameVideo . '.mp4 -i ' . DIRECTORY_MUSIC . $sound_name . ' -c:v copy -c:a aac -map 0:v:0 -map 1:a:0 ' . DIRECTORY_VIDEO . $resultName . '.mp4';
        $errors = shell_exec($ffmpeg . ' -hide_banner -loglevel error 2>&1');

        if (!is_null($errors)) {
            return ['status' => false, 'command' => $ffmpeg];
        }

        unlink(DIRECTORY_VIDEO . $nameVideo);
        return ['fileName' => $resultName, 'status' => true];
    }

    public function generatorImageFormat(string $nameImage, string $format): array
    {
        $infoImage = pathinfo(DIRECTORY_IMG . $nameImage);
        $resultName = $infoImage['filename'] . '_format5.' . $infoImage['extension'];
        $sizeImage = getimagesize(DIRECTORY_IMG . $nameImage);
        $proportion = $sizeImage[0] / $sizeImage[1];

        if ($format == '9/16') {
            $width = $sizeImage[1] * 9 / 16;
            $ffmpeg = 'ffmpeg -i ' . DIRECTORY_IMG . $nameImage . ' -vf crop=' . (int)$width . ':' . $sizeImage[1] . ' ' . DIRECTORY_IMG . $resultName . ' -y';
        }

        if ($format == '16/9') {
            if ($proportion <= 1.5) {
                $height = $sizeImage[0] * 9 / 16;
                $ffmpeg = 'ffmpeg -i ' . DIRECTORY_IMG . $nameImage . ' -vf crop=' . $sizeImage[0] . ':' . (int)$height . ' ' . DIRECTORY_IMG . $resultName. ' -y';
            } else {
                $width = $sizeImage[1] * 16 / 9;
                $ffmpeg = 'ffmpeg -i ' . DIRECTORY_IMG . $nameImage . ' -vf crop=' . (int)$width . ':' . $sizeImage[1] . ' ' . DIRECTORY_IMG . $resultName. ' -y';
            }
        }

        $errors = shell_exec($ffmpeg . ' -hide_banner -loglevel error 2>&1');

        if (!is_null($errors)) {
            return ['status' => false, 'fileName' => $resultName, 'command' => $ffmpeg];
        }

//        unlink(DIRECTORY_IMG . $nameImage);
        return ['fileName' => $resultName, 'status' => true];
    }

    /**Генерируем видео с нужного формата*/
    public function generatorVideoFormatForSlideShow(string $nameVideo, string $format): array
    {
        $resultName = $this->contentId . '_format';
        $ffmpeg = 'ffmpeg -i ' . DIRECTORY_ADDITIONAL_VIDEO . $nameVideo . '.mp4 -vf "scale=1080:-1,setdar=' . $format . '" ' . DIRECTORY_VIDEO . $resultName . '.mp4';
        $errors = shell_exec($ffmpeg . ' -hide_banner -loglevel error 2>&1');

        if (!is_null($errors)) {
            return ['status' => false];
        }

//        unlink(DIRECTORY_VIDEO . $nameVideo . '.mp4');
        return ['fileName' => $resultName, 'status' => true];
    }

    /**Генерируем видео с нужного формата*/
    public function generatorVideoFormat(string $nameVideo, string $format): array
    {
        $resultName = $this->contentId . '_format';
        $ffmpeg = 'ffmpeg -i ' . DIRECTORY_ADDITIONAL_VIDEO . $nameVideo . ' -vf "scale=1080:-1,setdar=' . $format . '" ' . DIRECTORY_VIDEO . $resultName . '.mp4';
        $errors = shell_exec($ffmpeg . ' -hide_banner -loglevel error 2>&1');

        var_dump($ffmpeg);
        if (!is_null($errors)) {
            return ['status' => false];
        }

        return ['fileName' => $resultName, 'status' => true];
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

        unlink(DIRECTORY_VIDEO . $videoName . '.mp4');
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

        unlink(DIRECTORY_VIDEO . $videoName . '.mp4');
        return ['fileName' => $resultName, 'status' => true];
    }

    /**Накладываем озвучку на видео*/
    public function generatorMusic(string $nameFileVoice, string $videoName): array
    {
        $resultName = $this->contentId . '_sound';

        $ffmpeg = 'ffmpeg -y -i ' . DIRECTORY_VIDEO . $videoName . '.mp4 -i ' . DIRECTORY_SPEECHKIT . $nameFileVoice . '.mp3 -filter_complex "[0]volume=0.4[a];[1]volume=1.8[b];[a][b]amix=inputs=2:duration=longest" -c:a libmp3lame ' . DIRECTORY_VIDEO . $resultName . '.mp4';
        $errors = shell_exec($ffmpeg . ' -hide_banner -loglevel error 2>&1');

        if (!is_null($errors)) {
            return ['status' => false, 'command' => $ffmpeg];
        }

        unlink(DIRECTORY_VIDEO . $videoName . '.mp4');
        return ['fileName' => $resultName, 'status' => true];
    }


    /**Склеиваем видео*/
    public function mergeVideo(string $nameVideoContent, ?string $nameVideoStart = null, ?string $nameVideoEnd = null): array
    {
        $fileName = $this->contentId . '_result1';
        $ffmpeg = 'ffmpeg -i "concat:';

        if (!is_null($nameVideoStart)) {
            $fileNameStart = str_replace('.mp4', '', $nameVideoStart);
            if ($this->mergeFiles($fileNameStart, DIRECTORY_ADDITIONAL_VIDEO)) {
                $ffmpeg .= DIRECTORY_ADDITIONAL_VIDEO . $fileNameStart . '.ts' . '|';
            } else {
                return ['status' => false];
            }
        }

        if ($this->mergeFiles($nameVideoContent, DIRECTORY_VIDEO)) {
            $ffmpeg .= DIRECTORY_VIDEO . $nameVideoContent . '.ts';
        } else {
            return ['status' => false];
        }


        if (!is_null($nameVideoEnd)) {
            $fileNameEnd = str_replace('.mp4', '', $nameVideoEnd);
            if ($this->mergeFiles($fileNameEnd, DIRECTORY_ADDITIONAL_VIDEO)) {
                $ffmpeg .= '|' . DIRECTORY_ADDITIONAL_VIDEO . $fileNameEnd;
            } else {
                return ['status' => false];
            }
        }

        $ffmpeg .= '" -vcodec copy -acodec copy ' . DIRECTORY_VIDEO . $fileName . '.mp4';
        $errors = shell_exec($ffmpeg . ' -hide_banner -loglevel error 2>&1');

        if (!is_null($errors)) {
            return ['status' => false];
        }

        return ['fileName' => $this->contentId, 'status' => true];
    }

    private function mergeFiles(string $fileName, string $directory): bool
    {

        $ffmpeg = 'ffmpeg -i ' . $directory . $fileName . '.mp4' . ' -acodec copy -vcodec copy -vbsf h264_mp4toannexb -f mpegts ' . $directory . $fileName . '.ts';
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
        $sound = '-i ' . DIRECTORY_MUSIC . $sound_name . ' ';


        $imagesString = implode(',', $arr_images);
        $images = ' -i ' . DIRECTORY_IMG . str_replace(',', ' -i ' . DIRECTORY_IMG, $imagesString) . ' ';

        $d = ceil((intval($sound_time) / count($arr_images)) * 25);
        $scale = '';
        $v = '';

        for ($i = 0; $i < count($arr_images); $i++) {
            $scale .= "[{$i}:v]scale=-1:10*ih,zoompan=z='min(zoom+0.0010,1.5)':d={$d}:x='iw/2-(iw/zoom/2)':y='ih/2-(ih/zoom/2)'[v{$i}];";
            $v .= "[v{$i}]";
        }

        $v = $v . ' concat=n=' . count($arr_images) . ':v=1:a=0,format=yuv422p[v]" -map "[v]" -map ' . count($arr_images) . ':a -shortest -y ' . DIRECTORY_VIDEO . $number . '.mp4';

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