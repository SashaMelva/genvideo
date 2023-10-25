<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportExcel extends Model
{
    public mixed $errors;
    protected $primaryKey = 'id';
    protected $table = 'import_excel';

    public static function getAllData(): array
    {
        return self::query()->get()->toArray();
    }

    public static function addFile(string $fileName, int $statusId): ImportExcel
    {
        $newList = new ImportExcel();
        $newList->setAttribute('file_name', $fileName);
        $newList->setAttribute('status', $newList->statusImport($statusId));
        $newList->save();
        return $newList;
    }

    public static function changeStatus(int $fileId, int $statusId, ?string $message = null): void
    {
        self::query()
            ->where([['id', '=', $fileId]])
            ->update(['status' => (new ImportExcel())->statusImport($statusId), 'message' => $message]);
    }

    private function statusImport(int $statusId): string
    {
        $res = [
            '1' => 'загружен',
            '2' => 'в обработке',
            '3' => 'созданы задачи на генерацию контента',
            '4' => 'успех',
            '5' => 'ошибка файл не найден',
            '6' => 'ошибка',
            '7' => 'сохранение данных в бд',
        ];
        return $res[$statusId];
    }
}