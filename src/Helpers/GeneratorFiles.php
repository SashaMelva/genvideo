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
    public function generatorText(string $videoName, string $titerName, string $formatVideo, array $textData): array
    {
        $resultName = $this->contentId . '_text';
        $stringDirectory = str_replace('\\', '\\\\', DIRECTORY_TEXT);
        $stringDirectory = str_replace(':', '\\:', $stringDirectory);

        if ($textData['text_color_background'] == 'Нет') {
            $colorOutline = '&HFF000000';
        } else {
            $colorOutline = is_null($textData['text_color_background']) || !str_contains($textData['text_color_background'], '&H') ? '&H80000000' : $textData['text_color_background'];
        }

        $colorText = is_null($textData['text_color']) || !str_contains($textData['text_color'], '&H') ? '&H00FFFFFF' : $textData['text_color'];

        if ($formatVideo == '9/16') {
            $ffmpeg = 'ffmpeg -i ' . DIRECTORY_VIDEO . $videoName . '.mp4 -filter_complex "subtitles=\'' . $stringDirectory . $titerName . '.ass' . '\':force_style=' .
                "'OutlineColour=$colorOutline,PrimaryColour=$colorText,BorderStyle=3,Outline=1,FontSize=12,Shadow=0,MarginV=110'" .
                '" -c:v h264_nvenc -c:a copy -y ' . DIRECTORY_VIDEO . $resultName . '.mp4';
        } else {
            $ffmpeg = 'ffmpeg -i ' . DIRECTORY_VIDEO . $videoName . '.mp4 -filter_complex "subtitles=\'' . $stringDirectory . $titerName . '.ass' . '\':force_style=' .
                "'OutlineColour=$colorOutline,PrimaryColour=$colorText,BorderStyle=3,Outline=1,Shadow=0,MarginV=110'" .
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
        $errors = '';
        $getID3 = new getID3;
        $fileSound = $getID3->analyze(DIRECTORY_MUSIC . $sound_name);
        $timeSound = $fileSound['playtime_seconds'];

        $this->log->info('Время озвучки текста ' . $timeVoice . ' Время фоновой музыки ' . $timeSound);
        if ($timeVoice > $timeSound) {
            $sound_name_long = explode('.', $sound_name)[0] . '_long.mp3';
            $loop = ceil($timeVoice / $timeSound);
            $ffmpeg = 'ffmpeg -stream_loop ' . $loop . ' -i ' . DIRECTORY_MUSIC . $sound_name . ' -c copy -t ' . ceil($timeVoice) . ' -y  ' . DIRECTORY_MUSIC . $sound_name_long;
            $errors = shell_exec($ffmpeg . ' -hide_banner -loglevel error 2>&1');
            $sound_name = $sound_name_long;
        }

        $getID3 = new getID3;
        $file = $getID3->analyze(DIRECTORY_ADDITIONAL_VIDEO . $nameVideo);
        $timeVideo = $file['playtime_seconds'];

        $this->log->info('Время фоновго видео ' . $timeVideo . ' Время фоновой музыки ' . $timeSound);
        if ($timeVoice > $timeVideo) {
            $resultName = $resultName . '_new.mp4';
            $loop = ceil($timeVoice / $timeVideo);
            $ffmpeg = 'ffmpeg  -stream_loop ' . $loop . ' -i ' . DIRECTORY_ADDITIONAL_VIDEO . $nameVideo . ' -i ' . DIRECTORY_MUSIC . $sound_name . ' -t ' . ceil($timeVoice) . ' -c:v h264_nvenc -c:a aac -map 0:v:0 -map 1:a:0 -y ' . DIRECTORY_VIDEO . $resultName . '.mp4';
        } else {
            $ffmpeg = 'ffmpeg -i ' . DIRECTORY_ADDITIONAL_VIDEO . $nameVideo . ' -i ' . DIRECTORY_MUSIC . $sound_name . ' -t ' . ceil($timeVoice) . ' -c:v h264_nvenc -c:a aac -map 0:v:0 -map 1:a:0 -y ' . DIRECTORY_VIDEO . $resultName . '.mp4';
        }

        $this->log->info($ffmpeg);
        $errors .= shell_exec($ffmpeg . ' -hide_banner -loglevel error 2>&1');

//        if (!is_null($errors)) {
//            return ['status' => false, 'command' => $ffmpeg];
//        }

//        if (file_exists(DIRECTORY_MUSIC . $sound_name)) {
//            unlink(DIRECTORY_MUSIC . $sound_name);
//        }
//        unlink(DIRECTORY_VIDEO . $nameVideo . '.mp4');
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
        $fileName = explode('.', $nameVideo);
        $resultName = $fileName[0] . '_format.mp4';
        $ffmpeg = 'ffmpeg -i ' . DIRECTORY_ADDITIONAL_VIDEO . $nameVideo . ' -vf "crop=((9*in_h)/16):in_h:in_w/2-((9*in_h)/16)/2:0" -c:v h264_nvenc -c:a copy -y ' . DIRECTORY_ADDITIONAL_VIDEO . $resultName;
        $this->log->info($ffmpeg);
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

        $ffmpeg = 'ffmpeg -i ' . DIRECTORY_VIDEO . $videoName . '.mp4 -i ' . DIRECTORY_SPEECHKIT . $nameFileVoice . '.mp3 -filter_complex "[0]volume=0.4[a];[1]volume=1.8[b];[a][b]amix=inputs=2:duration=longest" -c:v h264_nvenc -c:a libmp3lame -y ' . DIRECTORY_VIDEO . $resultName . '.mp4';
        $errors = shell_exec($ffmpeg . ' -hide_banner -loglevel error 2>&1');

        if (!is_null($errors)) {
            return ['status' => false, 'command' => $ffmpeg];
        }

        unlink(DIRECTORY_VIDEO . $videoName);
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

    public function generatorAdditionalVideoFormatMp4(string $nameVideo): array
    {
        $resultName = $nameVideo . '_format';

        $this->log->info('Форматирование конечного видео под размер 9/16');
        $ffmpeg = 'ffmpeg -i ' . DIRECTORY_ADDITIONAL_VIDEO . $nameVideo . '.mp4 -vf "crop=((9*in_h)/16):in_h:in_w/2-((9*in_h)/16)/2:0" -c:v h264_nvenc -c:a copy -y ' . DIRECTORY_ADDITIONAL_VIDEO . $resultName . '.mp4';
        $this->log->info($ffmpeg);
        $errors = shell_exec($ffmpeg . ' -hide_banner -loglevel error 2>&1');

        if (!is_null($errors)) {
            return ['status' => false];
        }

        //unlink(DIRECTORY_ADDITIONAL_VIDEO . $nameVideo . '.mp4');
        return ['fileName' => $resultName, 'status' => true];
    }

    public function bringingVideoSameSize(string $nameVideo, string $directory): array
    {
        $resultNameScale = $nameVideo . '_scale';
        $resultNameSetsar = $nameVideo . '_setsar';
        $errors = '';

        $this->log->info('Увлечение размера видео');
        $ffmpeg = 'ffmpeg -i ' . $directory . $nameVideo . '.mp4  -vf "scale=1920:1080" -c:v h264_nvenc -c:a copy -y ' . $directory . $resultNameScale . '.mp4';
        $this->log->info($ffmpeg);
        $errors .= shell_exec($ffmpeg . ' -hide_banner -loglevel error 2>&1');

        $this->log->info('Изменение соотношенияя сторон');
        $ffmpeg = 'ffmpeg -i ' . $directory . $resultNameScale . '.mp4  -vf "setsar=1:1" -c:v h264_nvenc -c:a copy -y ' . $directory . $resultNameSetsar . '.mp4';
        $this->log->info($ffmpeg);
        $errors .= shell_exec($ffmpeg . ' -hide_banner -loglevel error 2>&1');

        $this->log->info($errors);
        if (!empty($errors)) {
            return ['status' => false];
        }

        //unlink(DIRECTORY_ADDITIONAL_VIDEO . $nameVideo . '.mp4');
        return ['fileName' => $resultNameSetsar, 'status' => true];
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
                    $ffmpeg .= ' -i ' . DIRECTORY_ADDITIONAL_VIDEO . $fileNameStart . '.ts';
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
        if ($countVideo == 2) {
            $ffmpeg .= ' -filter_complex "[0:v] [0:a] [1:v] [1:a] concat=n=2:v=1:a=1 [v] [a]" -map "[v]" -map "[a]" ' . DIRECTORY_VIDEO . $fileName . '.mp4';
        }

        if ($countVideo == 3) {
            $ffmpeg .= ' -filter_complex "[0:v] [0:a] [1:v] [1:a] [2:v] [2:a] concat=n=3:v=1:a=1 [v] [a]" -map "[v]" -map "[a]" ' . DIRECTORY_VIDEO . $fileName . '.mp4';
        }
        $this->log->info($ffmpeg);
        $errors = shell_exec($ffmpeg . ' -hide_banner -loglevel error 2>&1');

        if (!is_null($errors)) {
            return ['status' => false, 'command' => $ffmpeg];
        }

        return ['fileName' => $fileName, 'status' => true];
    }

    public function mergeVideoWithSize(string $nameVideoContent, string $format, ?string $nameVideoStart = null, ?string $nameVideoEnd = null): array
    {
        $fileName = $this->contentId . '_result';
        $ffmpeg = 'ffmpeg ';
        $countVideo = 1;

        if (!is_null($nameVideoStart)) {
            $countVideo += 1;
            $fileNameStart = str_replace('.mp4', '', $nameVideoStart);


            if ($format == '9/16') {
                $this->log->info('Форматирование стартовое видео под разрешение 9/16');
                $dataStartVideo = $this->generatorAdditionalVideoFormatMp4($fileNameStart);

                if (!$dataStartVideo['status']) {
                    return ['status' => false, 'command' => $ffmpeg];
                }

            }

            $this->log->info('Форматирование начального видео');
            $fileStartVideoFormat = $this->bringingVideoSameSize($fileNameStart, DIRECTORY_ADDITIONAL_VIDEO);
            $this->log->info(json_encode($fileStartVideoFormat));

            if ($fileStartVideoFormat['status']) {
                $ffmpeg .= ' -i ' . DIRECTORY_ADDITIONAL_VIDEO . $fileStartVideoFormat['fileName'] . '.mp4';
            } else {
                return ['status' => false, 'command' => $ffmpeg];
            }

        }

        $this->log->info('Преобразование основного видео в формат');
        $fileMainVideoFormat = $this->bringingVideoSameSize($nameVideoContent, DIRECTORY_VIDEO);

        $this->log->info('Статус форматирования ' . $fileMainVideoFormat['status']);
        $this->log->info(json_encode($fileMainVideoFormat));

        if ($fileMainVideoFormat['status']) {
            $ffmpeg .= ' -i ' . DIRECTORY_VIDEO . $fileMainVideoFormat['fileName'] . '.mp4';
        } else {
            return ['status' => false, 'command' => $ffmpeg];
        }

        if (!is_null($nameVideoEnd)) {
            $countVideo += 1;
            $fileNameEnd = str_replace('.mp4', '', $nameVideoEnd);

            if ($format == '9/16') {
                $this->log->info('Форматирование конечное видео под разрешение 9/16');
                $dataEndVideo = $this->generatorAdditionalVideoFormatMp4($fileNameEnd);

                if (!$dataEndVideo['status']) {
                    return ['status' => false, 'command' => $ffmpeg];
                }
                $fileNameEnd = $dataEndVideo['fileName'];
            }

            $this->log->info('Форматирование конечного видео');
            $fileEndVideoFormat = $this->bringingVideoSameSize($fileNameEnd, DIRECTORY_ADDITIONAL_VIDEO);
            $this->log->info('Статус форматирования ' . $fileMainVideoFormat['status']);

            if ($fileEndVideoFormat['status']) {
                $ffmpeg .= ' -i ' . DIRECTORY_ADDITIONAL_VIDEO . $fileEndVideoFormat['fileName'] . '.mp4';
            } else {
                return ['status' => false, 'command' => $ffmpeg];
            }

        }

        $this->log->info('Количество видео для склейки ' . $countVideo);
        if ($countVideo == 2) {
            $ffmpeg .= ' -filter_complex "[0:v] [0:a] [1:v] [1:a] concat=n=2:v=1:a=1 [v] [a]" -map "[v]" -map "[a]" -y ' . DIRECTORY_VIDEO . $fileName . '.mp4';
        }

        if ($countVideo == 3) {
            $ffmpeg .= ' -filter_complex "[0:v] [0:a] [1:v] [1:a] [2:v] [2:a] concat=n=3:v=1:a=1 [v] [a]" -map "[v]" -map "[a]" -y ' . DIRECTORY_VIDEO . $fileName . '.mp4';
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
        $getID3 = new getID3;
        $fileSound = $getID3->analyze(DIRECTORY_MUSIC . $sound_name);
        $timeSound = $fileSound['playtime_seconds'];

        if ($sound_time > $timeSound) {
            $sound_name_long = explode('.', $sound_name)[0] . '_long.mp3';
            $loop = ceil($sound_time / $timeSound);
            $ffmpeg = 'ffmpeg -stream_loop ' . $loop . ' -i ' . DIRECTORY_MUSIC . $sound_name . ' -c copy -t ' . ceil($sound_time) . ' -y ' . DIRECTORY_MUSIC . $sound_name_long;
            $errors = shell_exec($ffmpeg . ' -hide_banner -loglevel error 2>&1');
            $sound_name = $sound_name_long;
        }

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
}