<?php

namespace App\Helpers;

use getID3;
use Monolog\Logger;

class GeneratorFiles
{
    private int $contentId;
    private Logger $log;

    public function __construct($contentId, $log)
    {
        $this->contentId = $contentId;
        $this->log = $log;
    }

    /**Генерируем субтитры*/
    public function generatorText(string $videoName, string $titerName, string $formatVideo): array
    {
        $resultName = $this->contentId . '_text';
        $stringDirectory = str_replace('\\', '\\\\', DIRECTORY_TEXT);
        $stringDirectory = str_replace(':', '\\:', $stringDirectory);

        if ($formatVideo == '9/16') {
            $ffmpeg = 'ffmpeg -i ' . DIRECTORY_VIDEO . $videoName . '.mp4 -filter_complex "subtitles=\'' . $stringDirectory . $titerName . '.ass' . '\':force_style=' .
                "'OutlineColour=&H80000000,BorderStyle=3,Outline=1,FontSize=12,Shadow=0,MarginV=110'" .
                '" -c:v h264_nvenc -c:a copy -y ' . DIRECTORY_VIDEO . $resultName . '.mp4';
        } else {
            $ffmpeg = 'ffmpeg -i ' . DIRECTORY_VIDEO . $videoName . '.mp4 -filter_complex "subtitles=\'' . $stringDirectory . $titerName . '.ass' . '\':force_style=' .
                "'OutlineColour=&H80000000,BorderStyle=3,Outline=1,Shadow=0,MarginV=110'" .
                '" -c:v h264_nvenc -c:a copy -y ' . DIRECTORY_VIDEO . $resultName . '.mp4';
        }

        $errors = shell_exec($ffmpeg . ' -hide_banner -loglevel error 2>&1');

        if (!is_null($errors)) {
            return ['status' => false, 'command' => $ffmpeg];
        }

        unlink(DIRECTORY_VIDEO . $videoName . '.mp4');
        return ['fileName' => $resultName, 'status' => true];
    }

    /**Генерируем cлайдшоу с фоновой музыкой*/
    public function generatorSladeShow(array $images, string $sound_name, string $time, string $format): array
    {
        $ffmpeg = $this->getSlideShowCode($images, $sound_name, $time, $format);

        $errors = shell_exec($ffmpeg . ' -hide_banner -loglevel error 2>&1');

        if (!is_null($errors)) {
            return ['status' => false, 'command' => $ffmpeg];
        }

        return ['fileName' => $this->contentId, 'status' => true];
    }

    /**Генерируем видео с фоновой музыкой*/
    public function generatorBackgroundVideoAndMusic(string $nameVideo, string $sound_name, string $timeVoice): array
    {
        $resultName = $this->contentId . '_music';

//        $getID3 = new getID3;
//        $file = $getID3->analyze(DIRECTORY_MUSIC . $sound_name);
//        $timeSound = $file['playtime_seconds'];

        $getID3 = new getID3;
        $file = $getID3->analyze(DIRECTORY_ADDITIONAL_VIDEO . $nameVideo);
        var_dump(DIRECTORY_ADDITIONAL_VIDEO . $nameVideo);
        $timeVideo = $file['playtime_seconds'];

        if ($timeVoice > $timeVideo) {
            $loop = floor($timeVoice / $timeVideo);
            $ffmpeg = 'ffmpeg  -stream_loop ' . $loop . ' -i ' . DIRECTORY_ADDITIONAL_VIDEO . $nameVideo . ' -i ' . DIRECTORY_MUSIC . $sound_name . ' -c:v h264_nvenc -c:a aac -map 0:v:0 -map 1:a:0 ' . DIRECTORY_VIDEO . $resultName . '_new.mp4';
        } else {
            $ffmpeg = 'ffmpeg -i ' . DIRECTORY_ADDITIONAL_VIDEO . $nameVideo . ' -i ' . DIRECTORY_MUSIC . $sound_name . ' -c:v h264_nvenc -c:a aac -map 0:v:0 -map 1:a:0 ' . DIRECTORY_VIDEO . $resultName . '_new.mp4';
        }

        $errors = shell_exec($ffmpeg . ' -hide_banner -loglevel error 2>&1');
        if (!is_null($errors)) {
            return ['status' => false, 'command' => $ffmpeg];
        }

        //$timeFormat = $this->formatMilliseconds($timeVoice * 1000);
        $ffmpeg = "ffmpeg -i " . DIRECTORY_VIDEO . $resultName . "_new.mp4 -t " .$timeVoice." -c:v h264_nvenc -c:a aac " . DIRECTORY_VIDEO . $resultName . '.mp4';

        $errors = shell_exec($ffmpeg . ' -hide_banner -loglevel error 2>&1');
        if (!is_null($errors)) {
            return ['status' => false, 'command' => $ffmpeg];
        }

        unlink(DIRECTORY_VIDEO . $nameVideo . '.mp4');
        return ['fileName' => $resultName, 'status' => true, 'command' => $ffmpeg];
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
                $ffmpeg = 'ffmpeg -i ' . DIRECTORY_IMG . $nameImage . ' -vf crop=' . $sizeImage[0] . ':' . (int)$height . ' ' . DIRECTORY_IMG . $resultName . ' -y';
            } else {
                $width = $sizeImage[1] * 16 / 9;
                $ffmpeg = 'ffmpeg -i ' . DIRECTORY_IMG . $nameImage . ' -vf crop=' . (int)$width . ':' . $sizeImage[1] . ' ' . DIRECTORY_IMG . $resultName . ' -y';
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
        $ffmpeg = 'ffmpeg -i ' . DIRECTORY_VIDEO . $nameVideo . '.mp4 -vf "scale=1080:-1,setdar=' . $format . '" -c:v h264_nvenc -c:a copy ' . DIRECTORY_VIDEO . $resultName . '.mp4';
        $errors = shell_exec($ffmpeg . ' -hide_banner -loglevel error 2>&1');

        if (!is_null($errors)) {
            return ['status' => false];
        }

        unlink(DIRECTORY_VIDEO . $nameVideo . '.mp4');
        return ['fileName' => $resultName, 'status' => true];
    }

    /**Генерируем видео с нужного формата*/
    public function generatorVideoFormat(string $nameVideo): array
    {
        $resultName = $this->contentId . '_format';
        $ffmpeg = 'ffmpeg -i ' . DIRECTORY_ADDITIONAL_VIDEO . $nameVideo . ' -vf "crop=((9*in_h)/16):in_h:in_w/2-((9*in_h)/16)/2:0" -y ' . DIRECTORY_ADDITIONAL_VIDEO . $resultName . '.mp4';
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
        $ffmpeg = 'ffmpeg -i ' . DIRECTORY_VIDEO . $videoName . '.mp4 -i ' . DIRECTORY_BACKGROUND . $nameFileBackground . ' -filter_complex "[0:v][1:v]overlay=0:0" -c:v h264_nvenc -c:a copy -y ' . DIRECTORY_VIDEO . $resultName . '.mp4';
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
        $ffmpeg = 'ffmpeg -i ' . DIRECTORY_VIDEO . $videoName . '.mp4 -i ' . DIRECTORY_LOGO_IMG . $nameFileLogo . ' -filter_complex "[1:v][0:v]scale2ref=(450/142)*ih/14/sar:ih/14[wm][base];[base][wm]overlay=main_w-overlay_w-10:10:format=rgb" -pix_fmt yuv420p -c:v h264_nvenc -c:a copy -y ' . DIRECTORY_VIDEO . $resultName . '.mp4';
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

        $ffmpeg = 'ffmpeg -y -i ' . DIRECTORY_VIDEO . $videoName . '.mp4 -i ' . DIRECTORY_SPEECHKIT . $nameFileVoice . '.mp3 -filter_complex "[0]volume=0.4[a];[1]volume=1.8[b];[a][b]amix=inputs=2:duration=longest" -c:v h264_nvenc -c:a libmp3lame ' . DIRECTORY_VIDEO . $resultName . '.mp4';
        $errors = shell_exec($ffmpeg . ' -hide_banner -loglevel error 2>&1');

        if (!is_null($errors)) {
            return ['status' => false, 'command' => $ffmpeg];
        }

        unlink(DIRECTORY_VIDEO . $videoName . '.mp4');
        return ['fileName' => $resultName, 'status' => true];
    }

    public function generatorAdditionalVideoFormat(string $nameVideo): array
    {
        $resultName = $nameVideo . '_format';

        if (file_exists(DIRECTORY_ADDITIONAL_VIDEO . $resultName . '.ts')) {
            return ['fileName' => $resultName, 'status' => true];
        }

        $ffmpeg = 'ffmpeg -i ' . DIRECTORY_ADDITIONAL_VIDEO . $nameVideo . '.mp4 -vf "crop=((9*in_h)/16):in_h:in_w/2-((9*in_h)/16)/2:0" -c:v h264_nvenc -c:a copy -f mpegts -y ' . DIRECTORY_ADDITIONAL_VIDEO . $resultName . '.ts';
        $this->log->info($ffmpeg);
        $errors = shell_exec($ffmpeg . ' -hide_banner -loglevel error 2>&1');
        
        if (!is_null($errors)) {
            return ['status' => false];
        }

        //unlink(DIRECTORY_ADDITIONAL_VIDEO . $nameVideo . '.mp4');
        return ['fileName' => $resultName, 'status' => true];
    }

    /**Склеиваем видео*/
    public function mergeVideo(string $nameVideoContent, string $format, ?string $nameVideoStart = null, ?string $nameVideoEnd = null): array
    {
        $fileName = $this->contentId . '_result';
        $ffmpeg = 'ffmpeg ';
        $countVideo = 1;

        if (!is_null($nameVideoStart)) {
            $countVideo += 1;
            $fileNameStart = str_replace('.mp4', '', $nameVideoStart);

           
            if ($format == '9/16') {
                $this->log->info('Форматирование стартовое видео под разрешение 9/16');
                $dataStartVideo = $this->generatorAdditionalVideoFormat($fileNameStart);

                if ($dataStartVideo['status']) {
                    $ffmpeg .= ' -i ' . DIRECTORY_ADDITIONAL_VIDEO . $dataStartVideo['fileName'] . '.ts';
                } else {
                    return ['status' => false, 'command' => $ffmpeg];
                }

            } else {
                $this->log->info('Преобразование начального видео в формат ts');
                
                if ($this->mergeFiles($fileNameStart, DIRECTORY_ADDITIONAL_VIDEO)) {
                    $ffmpeg .= ' -i ' . DIRECTORY_ADDITIONAL_VIDEO . $fileNameStart . '.ts' ;
                } else {
                    return ['status' => false, 'command' => $ffmpeg];
                }
            }
        }

        $this->log->info('Преобразование основного видео в формат ts');
        if ($this->mergeFiles($nameVideoContent, DIRECTORY_VIDEO)) {
            $ffmpeg .= ' -i ' . DIRECTORY_VIDEO . $nameVideoContent . '.ts';
        } else {
            return ['status' => false, 'command' => $ffmpeg];
        }


        if (!is_null($nameVideoEnd)) {
            $countVideo += 1;
            $fileNameEnd = str_replace('.mp4', '', $nameVideoEnd);

            if ($format == '9/16') {
                $this->log->info('Форматирование конечное видео под разрешение 9/16');
                $dataEndVideo = $this->generatorAdditionalVideoFormat($fileNameEnd);

                if ($dataEndVideo['status']) {
                    $ffmpeg .= ' -i ' . DIRECTORY_ADDITIONAL_VIDEO . $dataEndVideo['fileName'] . '.ts';
                } else {
                    return ['status' => false, 'command' => $ffmpeg];
                }

            } else {
                $this->log->info('Преобразование конечного видео в формат ts');
                if ($this->mergeFiles($fileNameEnd, DIRECTORY_ADDITIONAL_VIDEO)) {
                    $ffmpeg .= ' -i ' . DIRECTORY_ADDITIONAL_VIDEO . $fileNameEnd . '.ts';
                } else {
                    return ['status' => false, 'command' => $ffmpeg];
                }
            }
        }

        $this->log->info('Количество видео для склейки ' . $countVideo);
        if ($countVideo == 2){
            $ffmpeg .= ' -filter_complex "[0:v] [0:a] [1:v] [1:a] concat=n=2:v=1:a=1 [v] [a]" -map "[v]" -map "[a]" ' . DIRECTORY_VIDEO . $fileName . '.mp4';
        }

        if ($countVideo == 3){
            $ffmpeg .= '" -filter_complex "[0:v] [0:a] [1:v] [1:a] [2:v] [2:a] concat=n=3:v=1:a=1 [v] [a]" -map "[v]" -map "[a]" ' . DIRECTORY_VIDEO . $fileName . '.mp4';
        }
        $this->log->info($ffmpeg);
        $errors = shell_exec($ffmpeg . ' -hide_banner -loglevel error 2>&1');

        if (!is_null($errors)) {
            return ['status' => false, 'command' => $ffmpeg];
        }

        return ['fileName' => $fileName, 'status' => true];
    }

    private function mergeFiles(string $fileName, string $directory): bool
    {

        $ffmpeg = 'ffmpeg -i ' . $directory . $fileName . '.mp4' . ' -c:v h264_nvenc -c:a copy -f mpegts -y ' . $directory . $fileName . '.ts';
        $this->log->info($ffmpeg);
        $errors = shell_exec($ffmpeg . ' -hide_banner -loglevel error 2>&1');

        if (!is_null($errors)) {
            return false;
        }

        return true;
    }

    private function getSlideShowCode(array $arr_images, string $sound_name, string $sound_time, string $format): string
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

        if ($format == '9/16') {
            $v = $v . ' concat=n=' . count($arr_images) . ':v=1:a=0,format=yuv422p[v]" -map "[v]" -map ' . count($arr_images) . ':a -shortest -aspect 9:16 -c:v h264_nvenc -y ' . DIRECTORY_VIDEO . $number . '.mp4';
        } else {
            $v = $v . ' concat=n=' . count($arr_images) . ':v=1:a=0,format=yuv422p[v]" -map "[v]" -map ' . count($arr_images) . ':a -shortest -c:v h264_nvenc -y ' . DIRECTORY_VIDEO . $number . '.mp4';
        }

        return 'ffmpeg' . $images . $sound . '-filter_complex "' . $scale . $v;
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