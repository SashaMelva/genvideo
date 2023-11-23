<?php

namespace App\Console;

use App\Models\Article;
use App\Models\ContentVideo;
use App\Models\ImportExcel;
use Illuminate\Database\Capsule\Manager as DB;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Spatie\SimpleExcel\SimpleExcelReader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class ImportExcelArticle extends Command
{
    private Logger $log;
    private bool $status_log;

    protected function configure(): void
    {
        $log = new Logger('info');
        $log->pushHandler(new RotatingFileHandler('../var/log/import-article-excel.log', 2, Logger::INFO));
        $log->pushHandler(new StreamHandler('php://stdout'));

        $this->log = $log;
        $this->status_log = true;

        parent::configure();

        $this
            ->setName('import-article-excel')
            ->setDescription('import-article-excel');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        date_default_timezone_set('Europe/Moscow');
        $cmd = '/usr/bin/supervisorctl stop import-article-excel';

        if ($this->status_log) {
            $this->log->info('Начало ' . date('Y-m-d H:i:s'));
        }

        $filesImport = DB::table('import_excel')->select('id', 'file_name', 'creator_id')->where([['status', '=', 'загружен'], ['type', '=', 2]])->get()->toArray();
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
                'id сайта',
                'название статьи',
                'текст для запроса',
                'метки',
                'рубрики',
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
                $this->log->info('Сохранениe данных для статьи ' . $row['название статьи']);

                $articleId = self::addItemArticle($row);

                if (!empty($row['текст для запроса'])) {
                    self::addItemChatGptRequest($row, $articleId);
                }

                $this->log->info('Успех');
            }

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
        if (is_null($rows['текст для запроса']) || is_null($rows['id проекта']) || is_null($rows['id сайта'])) {
            return false;
        }

        return true;
    }

    public function addItemArticle(array $row): int
    {
        $article = Article::addContent(
            $row['id проекта'],
            $row['название статьи'],
            $row['id сайта'],
            $row['рубрики'],
            $row['метки'],
        );
        return $article->id;
    }

    public function addItemChatGptRequest(array $row, int $articleId): int
    {
        return DB::table('GPT_chat_requests')->insert(
            [
                'text_request' => $row['текст для запроса'],
                'status_working' => 5,
                'article_id' => $articleId,
            ]
        );
    }
}