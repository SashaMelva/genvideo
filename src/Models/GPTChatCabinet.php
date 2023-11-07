<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GPTChatCabinet extends Model
{
    public mixed $errors;
    protected $primaryKey = 'id';
    protected $table = 'GPT_chat_cabinet';

    public static function findCabinetByStatusWork(int $cabinetId): array
    {
        return self::query()->where([['project_id', '=', $cabinetId]])
            ->get()->toArray();
    }

    public static function findCabinetById(int $cabinetId): array
    {
        return self::query()->where([['project_id', '=', $cabinetId]])
            ->get()->toArray();
    }

    public static function findAllFreeCabinetAndWork(): array
    {
        return self::query()
            ->where([
                ['status_cabinet', '=', true],
                ['status_work', '=', 1],
            ])
            ->get()->toArray();
    }

    public static function findOneFreeCabinetAndWork(): array
    {
        return self::query()
            ->where([
                ['status_cabinet', '=', true],
                ['status_work', '=', 1],
            ])
            ->get()->toArray()[0];
    }

    public static function changeStatusCabinet(int $id, bool $flag): void
    {
        self::query()
            ->where([['id', '=', $id]])
            ->update(['status_cabinet' => $flag]);
    }

    public static function changeStatusWorkCabinet(int $id, bool $flag, string $error): void
    {
        self::query()
            ->where([['id', '=', $id]])
            ->update(['status_work' => $flag, 'error' => $error]);
    }

    public static function findOne(int $id): Model|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Builder|array|null
    {
        return self::query()->find($id)->getModel();
    }
}