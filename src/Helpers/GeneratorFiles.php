<?php

namespace App\Helpers;

use App\Models\ContentVideo;
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
        $fontSize = 16;
        $margin = ',MarginL=45,MarginR=45';

        if ($textData['text_color_background'] == 'Нет') {
            $colorOutline = '&HFF000000';
        } else {
            $colorOutline = is_null($textData['text_color_background']) ? '&H80000000' : $textData['text_color_background'];
        }

        $colorText = is_null($textData['text_color']) ? '&H00FFFFFF' : $textData['text_color'];

        if ($formatVideo == '9/16') {
            $fontSize = 12;
            $margin = '';
        }

        if (is_null($textData['shadow']) || $textData['shadow'] == '0') {
            $ffmpeg = 'ffmpeg -i ' . DIRECTORY_VIDEO . $videoName . '.mp4 -filter_complex "subtitles=\'' . $stringDirectory . $titerName . '.ass' . '\':force_style=' .
                "'OutlineColour=$colorOutline,PrimaryColour=$colorText,BorderStyle=3,Outline=1,FontSize=$fontSize,Shadow=0,MarginV=110$margin'" .
                '" -c:v h264_nvenc -c:a copy -y ' . DIRECTORY_VIDEO . $resultName . '.mp4';
        } else {
            $ffmpeg = 'ffmpeg -i ' . DIRECTORY_VIDEO . $videoName . '.mp4 -filter_complex "subtitles=\'' . $stringDirectory . $titerName . '.ass' . '\':force_style=' .
                "'PrimaryColour=$colorText,Outline=0,FontSize=$fontSize,Shadow=" . $textData['shadow'] . ",BackColour=" . $textData['back_colour'] . ",MarginV=110$margin'" .
                '" -c:v h264_nvenc -c:a copy -y ' . DIRECTORY_VIDEO . $resultName . '.mp4';
        }

        $this->log->info($ffmpeg);
        $errors = shell_exec($ffmpeg . ' -hide_banner -loglevel error 2>&1');

        if (!is_null($errors)) {
            return ['status' => false, 'command' => $ffmpeg];
        }

//        unlink(DIRECTORY_VIDEO . $videoName . '.mp4');
        return ['fileName' => $resultName, 'status' => true];
    }

    /**Генерируем cлайдшоу с фоновой музыкой*/
    public function generatorSladeShow(array $images, string $sound_name, string $time, string $format): array
    {
        $ffmpeg = $this->getSlideShowCode($images, $sound_name, $time, $format);

        $this->log->info($ffmpeg);
        $errors = shell_exec($ffmpeg . ' -hide_banner -loglevel error 2>&1');

        if (!is_null($errors)) {
            return ['status' => false, 'command' => $ffmpeg];
        }

        return ['fileName' => $this->contentId, 'status' => true];
    }

    /**Генерируем видео с фоновой музыкой*/
    public function generatorBackgroundVideoAndMusic(array $nameVideos, string $sound_name, string $timeVoice): array
    {
        $resultName = $this->contentId . '_music';
        $resultNameLongVideo = $this->contentId . '_longVideoResult';
        $errors = '';
        $getID3 = new getID3;
        $fileSound = $getID3->analyze(DIRECTORY_MUSIC . $sound_name);
        $timeSound = $fileSound['playtime_seconds'];
        $timeVideo = 0;

        $this->log->info('Время озвучки текста ' . $timeVoice . ' Время фоновой музыки ' . $timeSound);
        if ($timeVoice > $timeSound) {
            $sound_name_long = explode('.', $sound_name)[0] . '_long.mp3';
            $loop = ceil($timeVoice / $timeSound);
            $ffmpeg = 'ffmpeg -stream_loop ' . $loop . ' -i ' . DIRECTORY_MUSIC . $sound_name . ' -c copy -t ' . ceil($timeVoice) . ' -y  ' . DIRECTORY_MUSIC . $sound_name_long;
            $errors = shell_exec($ffmpeg . ' -hide_banner -loglevel error 2>&1');
            $sound_name = $sound_name_long;
        }

        $getID3 = new getID3;
        $file = $getID3->analyze(DIRECTORY_ADDITIONAL_VIDEO . $nameVideos[0]);
        $timeVideo += $file['playtime_seconds'];

        $this->log->info('Время фоновго видео ' . $timeVideo . ' Время озвучки ' . $timeVoice);

        if (count($nameVideos) == 1) {
            if ($timeVoice > $timeVideo) {
                $resultName = $resultName . '_new';
                $loop = ceil($timeVoice / $timeVideo);
                $ffmpeg = 'ffmpeg  -stream_loop ' . $loop . ' -i ' . DIRECTORY_ADDITIONAL_VIDEO . $nameVideos[0] . ' -i ' . DIRECTORY_MUSIC . $sound_name . ' -t ' . ceil($timeVoice) . ' -c:v h264_nvenc -c:a aac -map 0:v:0 -map 1:a:0 -y ' . DIRECTORY_VIDEO . $resultName . '.mp4';
            } else {
                $ffmpeg = 'ffmpeg -i ' . DIRECTORY_ADDITIONAL_VIDEO . $nameVideos[0] . ' -i ' . DIRECTORY_MUSIC . $sound_name . ' -t ' . ceil($timeVoice) . ' -c:v h264_nvenc -c:a aac -map 0:v:0 -map 1:a:0 -y ' . DIRECTORY_VIDEO . $resultName . '.mp4';
            }
        } else {
            $ffmpegForVideoArray = [];
            $resultArrayVideo = [];

            foreach ($nameVideos as $nameVideo) {

                $nameVideoNoExtension = explode('.', $nameVideo)[0];
                $this->log->info('Преобразование видео ' . $nameVideo . ' в формат ts');
                if ($this->mergeFiles($nameVideoNoExtension, DIRECTORY_ADDITIONAL_VIDEO)) {
                    $getID3 = new getID3;
                    $file = $getID3->analyze(DIRECTORY_ADDITIONAL_VIDEO . $nameVideo);
                    $this->log->info($nameVideoNoExtension . '.ts Время файла ' . $file['playtime_seconds']);
                    $ffmpegForVideoArray[] = ['filePath' => DIRECTORY_ADDITIONAL_VIDEO . $nameVideoNoExtension . '.ts', 'time' => $file['playtime_seconds']];
                } else {
                    return ['status' => false];
                }
            }
            $this->log->info('Пользователь выбрал несколько фоновых видео' . json_encode($ffmpegForVideoArray));
            $counter = 0;
            $timeVideoForLong = 0;

            while ($timeVideoForLong < ceil($timeVoice)) {

                $this->log->info('Сумма времени видео' . $timeVideoForLong . 'Время видео ' . $ffmpegForVideoArray[$counter]['time'] . ' Название файла ' . $ffmpegForVideoArray[$counter]['filePath'] . ' Время озвучки ' . ceil($timeVoice));

                $timeVideoForLong += $ffmpegForVideoArray[$counter]['time'];
                $resultArrayVideo[] = $ffmpegForVideoArray[$counter]['filePath'];
                $counter += 1;

                if ($counter == count($ffmpegForVideoArray)) {
                    $counter = 0;
                }
            }

            $this->log->info('Склейка фоновых видео');
            $ffmpegForVideo = 'ffmpeg -i "concat:' . implode('|', $resultArrayVideo) . '" -vcodec  h264_nvenc -acodec copy  -y ' . DIRECTORY_ADDITIONAL_VIDEO . $resultNameLongVideo . '.mp4';
            $this->log->info($ffmpegForVideo);
            shell_exec($ffmpegForVideo . ' -hide_banner -loglevel error 2>&1');

            $ffmpeg = 'ffmpeg -i ' . DIRECTORY_ADDITIONAL_VIDEO . $resultNameLongVideo . '.mp4 -i ' . DIRECTORY_MUSIC . $sound_name . ' -t ' . ceil($timeVoice) . ' -c:v h264_nvenc -c:a aac -map 0:v:0 -map 1:a:0 -b:v 8000k -y ' . DIRECTORY_VIDEO . $resultName . '.mp4';
        }

        $this->log->info('Наложение звука на видео');
        $this->log->info($ffmpeg);
        $errors .= shell_exec($ffmpeg . ' -hide_banner -loglevel error 2>&1');

//        if (!is_null($errors)) {
//            return ['status' => false, 'command' => $ffmpeg];
//        }

        if (!file_exists(DIRECTORY_VIDEO . $resultName . '.mp4')) {
            return ['status' => false];
        }
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

    /** Генерируем превью */

    public function generatorPreview(string $videoName, string $textPreview): array
    {
        $videoName = $videoName . '.mp4';
        $firstPreviewName = $this->contentId . '_photo.jpg';
        $resultImage = $this->contentId . '_result.jpg';

        $textArrayWords = explode(' ', trim($textPreview));
        $text = '';
        $countChar = 25;
        $textArray = [];
        $countArray = count($textArrayWords);
        /** Проверка остальных предложения на количество символов */
        for ($i = 0; $i < $countArray; $i++) {

            if (iconv_strlen(trim($textArrayWords[$i])) + iconv_strlen($text) > $countChar) {
                $textArray[] = trim($text);
                $text = '';
            }

            $text .= trim($textArrayWords[$i]) . ' ';
            unset($textArrayWords[$i]);
        }

        $textArray[] = trim(implode(" ", $textArrayWords) . $text);

        $ffmpegTimeVideo = 'ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 ' . DIRECTORY_VIDEO . $videoName;
        $res = shell_exec($ffmpegTimeVideo);
        $this->log->info('Длина видео в секундах ' . $res);
        $secondVideo = rand(1, (int)$res);
        $formatSeconds = $this->formatMilliseconds($secondVideo * 1000);
        $this->log->info('Выбранная и отформатированная секунда ' . $formatSeconds);

        $this->log->info(json_encode($textArray));
        $this->log->info('Достаём кадр из видео');
        $ffmpeg = 'ffmpeg -ss ' . $formatSeconds . ' -i ' . DIRECTORY_VIDEO . $videoName . ' -frames:v 1 -y  ' . DIRECTORY_PREVIEW . $firstPreviewName;
        $this->log->info($ffmpeg);
        $errors = shell_exec($ffmpeg . ' -hide_banner -loglevel error 2>&1');

        $this->log->info('Узнаём параметры изображения');
        $identify = ' identify -format "%wx%h" ' . DIRECTORY_PREVIEW . $firstPreviewName;
        $whidthAndHeight = shell_exec($identify);
        $whidthPreview = explode('x', $whidthAndHeight)[0];
        $heightPreview = explode('x', $whidthAndHeight)[1];
        $this->log->info('Ширина и высота изображения ' . $whidthAndHeight);


        $magicCommand = 'convert /var/www/genvi-api/var/resources/preview/' . $firstPreviewName;
        $this->log->info('Перебираем текст ' . $whidthPreview . ' ' . $heightPreview);

//        if ($widthPreview >= 1200 && $widthPreview <= 1500) {
//            $width = 200;
//        } elseif ($widthPreview >= 1501 && $widthPreview <= 2500) {
//            $width = 300;
//        } elseif ($widthPreview >= 2501 && $widthPreview <= 4200) {
//            $width = 720;
//        }

        if ($whidthPreview >= 600 && $whidthPreview <= 700) {
            $marginTop = 40;
            $placeTop = 40;
            $marginLeft = 20;
            $fontSize = 32;
        } elseif ($whidthPreview >= 1200 && $whidthPreview <= 1500) {
            $marginTop = 80;
            $placeTop = 110;
            $marginLeft = 40;
            $fontSize = 84;
        } elseif ($whidthPreview >= 1501 && $whidthPreview <= 2500) {
            $marginTop = 80;
            $placeTop = 160;
            $marginLeft = 40;
            $fontSize = 126;
        } else {
            $marginTop = 80;
            $placeTop = 110;
            $marginLeft = 40;
            $fontSize = 268;
        }

        foreach ($textArray as $textValue) {
            $magicCommand .= ' -undercolor yellow -fill black -gravity northwest -font ' . DIRECTORY_FONTS . 'arial_bold.ttf  -pointsize ' . $fontSize . ' -size 1024x -annotate +' . $marginLeft . '+' . $marginTop . ' "' . $textValue . '"';
            $marginTop += $placeTop;
        }

        $magicCommand .= '  ' . DIRECTORY_PREVIEW . $resultImage;
        $this->log->info($magicCommand);
        shell_exec($magicCommand);

        if (file_exists(DIRECTORY_PREVIEW . $resultImage)) {
            return ['status' => true, 'previewName' => $resultImage];
        }

        if (!is_null($errors)) {
            return ['status' => false];
        }

        return ['status' => true, 'previewName' => $resultImage];
    }

    /**Генерируем логотип*/
    public function generatorLogo(string $nameFileLogo, string $videoName): array
    {
        $videoName = $videoName . '.mp4';
        $resultName = $this->contentId . '_logo';
        $width = 120;
        $firstPreviewName= $this->contentId . '.png';

        $this->log->info('Достаём кадр из видео');
        $ffmpeg = 'ffmpeg -ss 00:00:05 -i ' . DIRECTORY_VIDEO . $videoName . ' -frames:v 1 -y  ' . DIRECTORY_PREVIEW . $firstPreviewName;
        $this->log->info($ffmpeg);
        $errors = shell_exec($ffmpeg . ' -hide_banner -loglevel error 2>&1');

        $identify = 'identify -format "%wx%h" ' . DIRECTORY_PREVIEW . $firstPreviewName;
        $widthAndHeight = shell_exec($identify);
        $widthPreview = explode('x', $widthAndHeight)[0];
        $this->log->info("Разрешение", ['data' => $widthPreview]);
        unlink(DIRECTORY_PREVIEW . $firstPreviewName);

        if ($widthPreview >= 1200 && $widthPreview <= 1500) {
            $width = 200;
        } elseif ($widthPreview >= 1501 && $widthPreview <= 2500) {
            $width = 300;
        } elseif ($widthPreview >= 2501 && $widthPreview <= 4200) {
            $width = 720;
        }


        $ffmpeg = 'ffmpeg -i ' . DIRECTORY_VIDEO . $videoName . ' -i ' . DIRECTORY_LOGO_IMG . $nameFileLogo . ' -filter_complex "[1:v]scale=' . $width . ':-1,format=yuva420p [overlay]; [0:v][overlay] overlay=20:20" -c:v h264_nvenc -c:a copy -y ' . DIRECTORY_VIDEO . $resultName . '.mp4';
        shell_exec($ffmpeg);

        $this->log->info($ffmpeg);
//        if (!is_null($errors)) {
//            return ['status' => false];
//        }

//        unlink(DIRECTORY_VIDEO . $videoName . '.mp4');
        return ['fileName' => $resultName, 'status' => true];
    }

    /**Накладываем озвучку на видео*/
    public function generatorMusic(string $nameFileVoice, string $videoName): array
    {
        $resultName = $this->contentId . '_sound';

        $this->log->info('Начало наложения озвучки');
        $ffmpeg = 'ffmpeg -i ' . DIRECTORY_VIDEO . $videoName . '.mp4 -i ' . DIRECTORY_SPEECHKIT . $nameFileVoice . '.mp3 -filter_complex "[0]volume=0.4[a];[1]volume=1.8[b];[a][b]amix=inputs=2:duration=longest" -c:v h264_nvenc -c:a libmp3lame -y ' . DIRECTORY_VIDEO . $resultName . '.mp4';
        $this->log->info($ffmpeg);
        $errors = shell_exec($ffmpeg . ' -hide_banner -loglevel error 2>&1');

        if (!is_null($errors)) {
            return ['status' => false, 'command' => $ffmpeg];
        }

//        unlink(DIRECTORY_VIDEO . $videoName . '.mp4');
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
        $this->log->info('Видео для склейки ',[
            'nameVideoContent' => $nameVideoContent,
            'format'           => $format,
            'nameVideoStart'   => $nameVideoStart,
            'nameVideoEnd'     => $nameVideoEnd,
        ]);

        $fileName = $this->contentId . '_result';
        $ffmpeg = 'ffmpeg -i "concat:';

        // TODO временный фикс для генерации
        // TODO видео без концовки, в будущем лучше отредактировать функцию
        $ffmpeg_start = 'ffmpeg -i ';
        $countVideo = 1;

        if (!is_null($nameVideoStart)) {
            $this->log->info('Есть начало видео');
            $countVideo += 1;
            $fileNameStart = str_replace('.mp4', '', $nameVideoStart);


            if ($format == '9/16') {
                $this->log->info('Форматирование стартовое видео под разрешение 9/16');
                $dataStartVideo = $this->generatorAdditionalVideoFormat($fileNameStart);

                if ($dataStartVideo['status']) {

                    $ffmpeg_start .= DIRECTORY_ADDITIONAL_VIDEO . $dataStartVideo['fileName'] . '.ts' . ' -i ';
                  //  $ffmpeg .= DIRECTORY_ADDITIONAL_VIDEO . $dataStartVideo['fileName'] . '.ts' . '|';
                } else {
                    return ['status' => false, 'command' => $ffmpeg];
                }

            } else {
                $this->log->info('Преобразование начального видео в формат ts');

                if ($this->mergeFiles($fileNameStart, DIRECTORY_ADDITIONAL_VIDEO)) {
                    $ffmpeg_start .= DIRECTORY_ADDITIONAL_VIDEO . $fileNameStart . '.ts' . ' -i ';
                  //  $ffmpeg .= DIRECTORY_ADDITIONAL_VIDEO . $fileNameStart . '.ts' . '|';
                } else {
                    return ['status' => false, 'command' => $ffmpeg];
                }
            }
        }

        $this->log->info('Преобразование основного видео в формат ts');
        if ($this->mergeFiles($nameVideoContent, DIRECTORY_VIDEO)) {
            $ffmpeg .= DIRECTORY_VIDEO . $nameVideoContent . '.ts';
            $ffmpeg_start .= DIRECTORY_VIDEO . $nameVideoContent . '.ts';
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
                    $ffmpeg .= '|' . DIRECTORY_ADDITIONAL_VIDEO . $dataEndVideo['fileName'] . '.ts';
                } else {
                    return ['status' => false, 'command' => $ffmpeg];
                }

            } else {
                $this->log->info('Преобразование конечного видео в формат ts');
                if ($this->mergeFiles($fileNameEnd, DIRECTORY_ADDITIONAL_VIDEO)) {
                    $ffmpeg .= '|' . DIRECTORY_ADDITIONAL_VIDEO . $fileNameEnd . '.ts';
                } else {
                    return ['status' => false, 'command' => $ffmpeg];
                }
            }
        }

        $this->log->info('Количество видео для склейки ' . $countVideo);
        if (!is_null($nameVideoStart)) {
            $ffmpeg_start .= ' -filter_complex "[0:v][1:v]concat=n=2:v=1:a=0[vout]; [0:a][1:a]concat=n=2:v=0:a=1[aout]" -map "[vout]" -map "[aout]" -c:v h264_nvenc -c:a aac -y ' . DIRECTORY_VIDEO . $fileName . '.mp4';
        } else {
            $ffmpeg .= '" -vcodec  h264_nvenc -acodec copy -y ' . DIRECTORY_VIDEO . $fileName . '.mp4';
        }

//        if ($countVideo == 2) {
//            $ffmpeg .= ' -filter_complex "[0:v] [0:a] [1:v] [1:a] concat=n=2:v=1:a=1 [v] [a]" -map "[v]" -map "[a]" -y ' . DIRECTORY_VIDEO . $fileName . '.mp4';
//        }
//
//        if ($countVideo == 3) {
//            $ffmpeg .= ' -filter_complex "[0:v] [0:a] [1:v] [1:a] [2:v] [2:a] concat=n=3:v=1:a=1 [v] [a]" -map "[v]" -map "[a]" -y ' . DIRECTORY_VIDEO . $fileName . '.mp4';
//        }
        if (!is_null($nameVideoStart)) {
            $errors = shell_exec($ffmpeg_start . ' -hide_banner -loglevel error 2>&1');
            $this->log->info($ffmpeg_start);
        } else {
            $errors = shell_exec($ffmpeg . ' -hide_banner -loglevel error 2>&1');
            $this->log->info($ffmpeg);
        }

        if (!is_null($errors)) {
            if (!is_null($nameVideoStart)) {
                return ['status' => false, 'command' => $ffmpeg_start];
            } else {
                return ['status' => false, 'command' => $ffmpeg];
            }
        }

//        if (file_exists(DIRECTORY_ADDITIONAL_VIDEO . $dataEndVideo['fileName'] . '.ts')) {
//            unlink(DIRECTORY_ADDITIONAL_VIDEO . $dataEndVideo['fileName'] . '.ts');
//        }
//        if (file_exists(DIRECTORY_VIDEO . $nameVideoContent . '.ts')) {
//            unlink(DIRECTORY_VIDEO . $nameVideoContent . '.ts');
//        }
//        if (file_exists(DIRECTORY_VIDEO . $nameVideoContent . '.mp4')) {
//            unlink(DIRECTORY_VIDEO . $nameVideoContent . '.mp4');
//        }
//        if (file_exists()) {
//            unlink();
//        }

//        $this->log->info('Повышаем качество видео');
//        $ffmpeg = ' -filter_complex "[0:v] [0:a] [1:v] [1:a] [2:v] [2:a] concat=n=3:v=1:a=1 [v] [a]" -map "[v]" -map "[a]" -y ' . DIRECTORY_VIDEO . $fileName . '.mp4';
//        $this->log->info($ffmpeg);
//        $errors = shell_exec($ffmpeg . ' -hide_banner -loglevel error 2>&1');

        return ['fileName' => $fileName, 'status' => true];
    }

    public function mergeVideoWithSize(string $nameVideoContent, string $format, ?string $nameVideoStart = null, ?string $nameVideoEnd = null): array
    {
        $fileName = $this->contentId . '_result';
        $ffmpeg = 'ffmpeg';
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
            $ffmpeg .= ' -filter_complex  "[0:0][0:1][1:0][1:1] concat=n=2:v=1:a=1 [v] [a]" -map "[v]" -map "[a]" -vcodec  h264_nvenc -y ' . DIRECTORY_VIDEO . $fileName . '.mp4';
        }

        if ($countVideo == 3) {
            $ffmpeg .= ' -filter_complex  "[0:0][0:1][1:0][1:1][2:0][2:1] concat=n=3:v=1:a=1 [v] [a]" -map "[v]" -map "[a]" -vcodec  h264_nvenc -y ' . DIRECTORY_VIDEO . $fileName . '.mp4';
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
            $this->log->info('Зацикливание аудио');
            $sound_name_long = explode('.', $sound_name)[0] . '_long.mp3';
            $loop = ceil($sound_time / $timeSound);
            $ffmpeg = 'ffmpeg -stream_loop ' . $loop . ' -i ' . DIRECTORY_MUSIC . $sound_name . ' -c copy -t ' . ceil($sound_time) . ' -y ' . DIRECTORY_MUSIC . $sound_name_long;
            $this->log->info($ffmpeg);
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