<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ListRequestGPTCabinet extends Model
{
    public mixed $errors;
    protected $primaryKey = 'id';
    protected $table = 'list_GPT_chat_request';

    public static function findAllRequestByCabinetId(int $cabinetId): array
    {
        return self::query()->where([['project_id', '=', $cabinetId]])
            ->get()->toArray();
    }

    public static function findCabinetIdByRequestId(int $requestId): array
    {
        return self::query()
            ->select(
                'list_GPT_chat_request.crated_at AS created_list',
                'GPT_chat_cabinet.email',
                'GPT_chat_cabinet.password',
                'GPT_chat_cabinet.api_key',
                'GPT_chat_cabinet.created_at',
                'GPT_chat_cabinet.updated_at',
                'GPT_chat_cabinet.status_work',
                'GPT_chat_cabinet.created_at',
                'GPT_chat_cabinet.status_cabinet',
            )
            ->leftJoin('GPT_chat_cabinet', 'list_GPT_chat_request.id_cabinet', '=', 'GPT_chat_cabinet.id')
            ->where([['list_GPT_chat_request.id_request', '=', $requestId]])
            ->get()->toArray()[0];
    }

    public static function addNewList(int $requestId, int $cabinetId, int $statusWorking): void
    {
        self::query()->insert(
            [
                'id_request' => $requestId,
                'id_cabinet' => $cabinetId,
                'status_working' => $statusWorking,
                'created_at' => date('Y-m-d H:i:s'),
            ]
        );
    }

    public static function changeStatus(int $Id, int $statusWorking): void
    {
        self::query()
            ->where([
                ['id', '=', $Id],
            ])
            ->update(['status_working' => $statusWorking]);
    }
}