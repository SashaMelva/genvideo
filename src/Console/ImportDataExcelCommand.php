<?php

namespace App\Console;

use App\Models\ContentVideo;
use App\Models\ImportExcel;
use App\Models\ListMusic;
use App\Models\ListVideo;
use App\Models\Voice;
use Spatie\SimpleExcel\SimpleExcelReader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Illuminate\Database\Capsule\Manager as DB;
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Throwable;

class ImportDataExcelCommand extends Command
{
    private Logger $log;
    private bool $status_log;

    protected function configure(): void
    {
        $log = new Logger('info');
        $log->pushHandler(new RotatingFileHandler('../var/log/import-data-excel.log', 2, Logger::INFO));
        $log->pushHandler(new StreamHandler('php://stdout'));

        $this->log = $log;
        $this->status_log = true;

        parent::configure();

        $this
            ->setName('import-data-excel')
            ->setDescription('import-data-excel');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        date_default_timezone_set('Europe/Moscow');
        $cmd = '/usr/bin/supervisorctl stop import-data-excel';

        if ($this->status_log) {
            $this->log->info('Начало ' . date('Y-m-d H:i:s'));
        }

        $filesImport = DB::table('import_excel')->select('id', 'file_name', 'creator_id')->where([['status', '=', 'загружен']])->get()->toArray();
        // $filesImport = DB::table('import_excel')->select('id', 'file_name', 'creator_id')->get()->toArray();

        if (empty($filesImport)) {
            $this->log->info('Нет файлов для импорта');
            exec($cmd);
            return 0;
        }

        if ($this->status_log) {
            $this->log->info('Файлы для импорта : ' . json_encode($filesImport));
        }

        try {

            $exportItem = $filesImport[0];
            var_dump(DIRECTORY_EXCEL_IMPORT . $exportItem->file_name);
            $path = DIRECTORY_EXCEL_IMPORT . $exportItem->file_name;
            $allHeadersList = [
                'id проекта',
                'название видео',
                'формат видео',
                'текст для запроса',
                'текст для озвучки',
                'текст для превью',
                'id фоновой музыки',
                'задержка в милисикундах в конце видео',
                'субтитры',
                'цвет текста субтитров',
                'фон текст субтитров',
                'голос',
                'скорость голоса',
                'задержка между абзацами в миллисекундах',
                'задержка между предложениями в миллисекундах',
                'фоновое затемнение',
                'тип фона',
                'глубина тени',
                'цвет тени',
                'сгенерировать картинки',
                'id картинок',
                'id видео',
                'id логотипа',
                'id банера',
                'длительность банера',
                'когда показать банер',
                'id заставки в начале видео',
                'id заставки в конце видео',
                'id заставки после 1 абзаца',
            ];

            if (!file_exists($path)) {

                if ($this->status_log) {
                    $this->log->info('Файл не найден');
                    ImportExcel::changeStatus($exportItem->id, 5);
                }

                exec($cmd);
                return 0;
            }

            $excelRows = SimpleExcelReader::create($path)->formatHeadersUsing(fn($header) => mb_strtolower(trim($header)));
            $titles = $excelRows->getHeaders();
            $allRows = $excelRows->getRows()->toArray();

            if (count($titles) == 0) {
                $msg = 'Не найдены заголовки';
                $this->log->error($msg);
                ImportExcel::changeStatus($exportItem->id, 6, $msg);
                exec($cmd);
                return 0;
            }

            $rows = self::rowsForCurrentHeaders($allRows, $allHeadersList);

            if (count($rows) == 0) {
                $msg = 'Файл пустой';
                $this->log->error($msg);
                ImportExcel::changeStatus($exportItem->id, 6, $msg);
                exec($cmd);
                return 0;
            }

            if (count($rows[0]) != count($allHeadersList)) {
                $msg = 'Не хватает заголовков';
                $this->log->error($msg);
                ImportExcel::changeStatus($exportItem->id, 6, $msg);
                exec($cmd);
                return 0;
            }

            /** проверка заполнения обязательных полей - исключение не подходящих*/
            $this->log->info('Проверка заполнения обязательных полей');
            $msg = '';
            $rowsNoEmptyValue = [];
            foreach ($rows as $number => $row) {
                if (!self::indexesToCheck($row)) {
                    $msg .= ' Не заполнены обязательные поля, строка №' . $number + 2;
                    ImportExcel::changeStatus($exportItem->id, 6, $msg);

                } else {
                    $rowsNoEmptyValue[] = $row;
                }
            }

            if (!empty($msg)) {
                $this->log->info($msg);
                exec($cmd);
                return 0;
            }

            if ($this->status_log) {
                $this->log->info('Файл прошёл проверку, приступаем к сохранению данных в бд ' . date('Y-m-d H:i:s'));
            }

            ImportExcel::changeStatus($exportItem->id, 7);

            /**Распределение данных по таблицам*/
            foreach ($rowsNoEmptyValue as $row) {
                $this->log->info('Сохранениe данных для контента ' . $row['название видео']);
                $statusContent = 6;

                if (empty($row['текст для запроса'])) {
                    /**Сейчас не реализова поиск картинок в яндекс, поэтомуу если контекнт не надоотправлять в gpt, то он сразу идёт на генерацию*/
                    $statusContent = 1;
                }

                $voiceId = Voice::getBySpeakerName($row['голос'])[0]['id'];
                $generatorImageStatus = 0;
                $textId = self::addItemText($row);

                $contentId = self::addItemContent($row, (int)$exportItem->creator_id, $voiceId, $textId, $generatorImageStatus, $statusContent);

                if (!empty($row['текст для запроса'])) {
                    self::addItemChatGptRequest($row, $textId, $contentId);
                }

                ListMusic::addMusic($row['id фоновой музыки'], (int)$contentId);

                $idsImages = [];
                $idsVideo = [];

                if (!empty($row['id заставки в начале видео'])) {
                    $idsVideo[] = $row['id заставки в начале видео'];
                }
                if (!empty($row['id заставки в конце видео'])) {
                    $idsVideo[] = $row['id заставки в конце видео'];
                }
                if (!empty($row['id заставки после 1 абзаца'])) {
                    $idsVideo[] = $row['id заставки после 1 абзаца'];
                }


                if (!empty($row['id банера'])) {
                    self::addItemImage($row['id банера'], (int)$contentId, $row);
                }
                if (!empty($row['id логотипа'])) {
                    $idsImages[] = $row['id логотипа'];
                }

                if ($row['тип фона'] == 'Картинки' && !empty($row['id картинок'])) {
                    $arraySlideImage = explode(',', $row['id картинок']);
                    $idsImages = array_merge($arraySlideImage, $idsImages);
                }
                if ($row['тип фона'] == 'Видео') {
                    $arrayContentVideo = explode(',', $row['id видео']);
                    $idsVideo = array_merge($arrayContentVideo, $idsVideo);
                }

                foreach ($idsImages as $item) {
                    self::addItemImage((int)$item, (int)$contentId);
                }
                foreach ($idsVideo as $item) {
                    ListVideo::addVideo((int)$item, (int)$contentId);
                }

                $this->log->info('Успех');
            }

            /**Данные для контета*/


            ImportExcel::changeStatus($exportItem->id, 4);
            $this->log->info('Данные из файла успешно сгенерировались ' . $exportItem->id);

        } catch (Throwable $e) {
            $this->log->error($e->getMessage());
            ContentVideo::changeStatus($exportItem->id, 6);
        }

        if ($this->status_log) {
            $this->log->info('Выполнено ' . date('Y-m-d H:i:s'));
        }

        exec($cmd);
        return 0;
    }

    private static function rowsForCurrentHeaders(array $rows, array $headers): array
    {
        $result = [];
        foreach ($rows as $key => $row) {
            $dataForRow = [];
            foreach ($headers as $header) {

                if (isset($row[$header])) {
                    $dataForRow[$header] = $row[$header];
                }

            }
            $result[$key] = $dataForRow;
        }

        return $result;
    }

    private static function indexesToCheck(array $rows): bool
    {
        if (is_null($rows['id проекта']) ||
            is_null($rows['название видео']) ||
            is_null($rows['формат видео']) ||
            is_null($rows['id фоновой музыки']) ||
            is_null($rows['субтитры']) ||
            is_null($rows['голос']) ||
            is_null($rows['скорость голоса']) ||
            is_null($rows['задержка между предложениями в миллисекундах']) ||
            is_null($rows['задержка между абзацами в миллисекундах']) ||
            is_null($rows['фоновое затемнение']) ||
            is_null($rows['сгенерировать картинки']) ||
            is_null($rows['тип фона'])
        ) {
            return false;
        }

        if (is_null($rows['текст для запроса']) || is_null($rows['текст для озвучки'])) {
            return false;
        }

        if ($rows['формат видео'] == 'Есть' && (is_null($rows['цвет текста субтитров']) || is_null($rows['фон текст субтитров']))) {
            return false;
        }

        if (($rows['тип фона'] == 'Картинки' || $rows['тип фона'] == 'slide_show') && $rows['сгенерировать картинки'] == 'Нет' && is_null($rows['id картинок'])) {
            return false;
        }

        if (($rows['тип фона'] == 'Видео' || $rows['тип фона'] == 'video') && is_null($rows['id видео'])) {
            return false;
        }

        if (!is_null($rows['id банера']) && (is_null($rows['длительность банера']) || is_null($rows['когда показать банер']))) {
            return false;
        }

        return true;
    }

    public function addItemContent(array $row, int $creatorId, int $voiceId, int $textId, string $generatorImageStatus, int $statusId): int
    {
        $backgroundColor = [
            'Нет' => null,
            'Черное' => 1
        ];

        if ($row['тип фона'] == 'Картинки' || $row['тип фона'] == 'slide_show') {
            $typeBackground = 'slide_show';
        } elseif ($row['тип фона'] == 'Видео' || $row['тип фона'] == 'video') {
            $typeBackground = 'video';
        } else {
            $typeBackground = null;
        }

        $content = ContentVideo::addContent(
            $row['название видео'],
            $creatorId,
            null,
            null,
            $row['id проекта'],
            $typeBackground,
            $voiceId,
            $row['формат видео'],
            $backgroundColor[$row['фоновое затемнение']],
            $statusId,
            $textId,
            'good', #TODO интонацию аудио
            $generatorImageStatus,
            floor($row['задержка между абзацами в миллисекундах'] / 1000),
            $row['текст для превью']
        );
        return $content->id;
    }

    public function addItemText(array $row): int
    {
        $subtitles = trim($row['субтитры']) == 'Есть';

        return DB::table('text')->insertGetId(
            [
                'project_id' => $row['id проекта'],
                'text' => $row['текст для озвучки'],
                'status_voice' => 0,
                'status_text' => 0,
                'text_color' => strtolower(trim($row['цвет текста субтитров'])),
                'text_color_background' => strtolower(trim($row['фон текст субтитров'])),
                'subtitles' => $subtitles,
                'voice_speed' => $row['скорость голоса'],
                'delay_between_offers' => $row['задержка между предложениями в миллисекундах'],
                'delay_between_paragraphs' => $row['задержка между абзацами в миллисекундах'],
                'updated_at' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s'),
                'shadow' => $row['глубина тени'],
                'back_colour' => $row['цвет тени'],
            ]
        );
    }

    public function addItemImage(int $imageId, int $contentId, ?array $row = null): bool
    {
        return DB::table('list_image')->insert(
            [
                'image_id' => $imageId,
                'content_id' => $contentId,
                'duration_show' => $row['длительность банера'] ?? null,
                'time_show' => $row['когда показать банер'] ?? null
            ]
        );
    }

    public function addItemChatGptRequest(array $row, int $textId, int $contentId): int
    {
        return DB::table('GPT_chat_requests')->insert(
            [
                'content_id' => $contentId,
                'text_request' => $row['текст для запроса'],
                'status_working' => 5,
                'text_id' => $textId,
            ]
        );
    }
}