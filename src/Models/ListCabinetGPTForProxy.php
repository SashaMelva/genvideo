<?php

namespace App\Models;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model;

class ListCabinetGPTForProxy extends Model
{
    public mixed $errors;
    protected $primaryKey = 'id';
    protected $table = 'list_cabinet_for_proxy';

    public static function findProxyByCabinetId(int $cabinetId): array
    {
        return self::query()
            ->select(
                'proxy.id',
                'proxy.ip_address',
                'proxy.user_name',
                'proxy.password',
                'proxy.port',
                'proxy.status_work',
                'proxy.created_at',
                'proxy.updated_at',
            )
            ->leftJoin('proxy', 'list_cabinet_for_proxy.id_proxy', '=', 'proxy.id')
            ->where([['list_cabinet_for_proxy.id_cabinet', '=', $cabinetId]])
            ->get()->toArray()[0];
    }

    public static function findAllCabinetIdByRequestId(int $requestId): array
    {
        return self::query()->where([['user_id', '=', $requestId]])
            ->get()->toArray();
    }
}