<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class Website extends Model
{
    public mixed $errors;
    protected $primaryKey = 'id';
    protected $table = 'websites';


    public static function findOne(int $id): Model|Collection|Builder|array|null
    {
        return self::query()->find($id)->getModel();
    }

    public static function findByUserId(int $id): Model|Builder|null
    {
        return self::query()
            ->where([['id', '=', $id]])
            ->first();
    }

    public static function findAll(): array
    {
        return self::query()
            ->get()->toArray();
    }

    public static function accessCheckCreator(int $projectId, int $userid): bool
    {
        $access = self::query()
            ->where([['id', '=', $projectId]])
            ->get()->toArray()[0];

        return $access['creator_id'] == $userid;
    }

    public static function fullInfo(int $id): array
    {
        return self::query()
            ->select([
                'projects.id AS project_id',
                'projects.name AS project_name',
                'projects.creator_id',
                'projects.created_at AS project_created_at',
                'projects.updated_at AS project_updated_at',
                'users.name AS creator_name',
                'users.email AS creator_email',
                'users.role AS creator_role',
                'users.phone AS creator_phone',
            ])
            ->leftJoin('users', 'users.id', '=', 'projects.creator_id')
            ->where([['projects.id', '=', $id]])
            ->get()->toArray()[0];
    }

    public static function fullContent(int $id): array
    {
        return self::query()
            ->select([
                'projects.id AS project_id',
                'projects.name AS project_name',
                'projects.creator_id',
                'projects.created_at AS project_created_at',
                'projects.updated_at AS project_updated_at',
                'users.name AS creator_name',
                'users.email AS creator_email',
                'users.role AS creator_role',
                'users.phone AS creator_phone',
            ])
            ->leftJoin('users', 'users.id', '=', 'projects.creator_id')
            ->where([['projects.id', '=', $id]])
            ->get()->toArray();
    }

    public static function updateName(int $id, array $date): void
    {
        self::query()
            ->where([['id', '=', $id]])
            ->update([
                'domen' => $date['domen'],
                'user_name' => $date['user_name'],
                'password_app' => $date['password_app'],
                'name' => $date['name'],
                'description' => $date['description'],
                ]);
    }
}