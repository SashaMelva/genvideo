<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ListProject extends Model
{
    public mixed $errors;
    protected $primaryKey = 'id';
    protected $table = 'list_project';

    public static function findAllUsersByProjectId(int $projectId): array
    {
        return self::query()->where([['project_id', '=', $projectId]])
            ->get()->toArray();
    }

    public static function findAllProjectIdByUserId(int $userId): array
    {
        return self::query()->where([['user_id', '=', $userId]])
            ->get()->toArray();
    }

    public static function findAllProjectInfoByUserId(int $userId): array
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
            ->leftJoin('projects', 'projects.id', '=', 'list_project.project_id')
            ->leftJoin('users', 'projects.creator_id', '=', 'users.id')
            ->where([['list_project.user_id', '=', $userId]])
            ->get()->toArray();
    }

    public static function addProject(int $userId, int $projectId): ListProject
    {
        var_dump($userId);
        var_dump($projectId);
        $newList = new ListProject();
        $newList->setAttribute('user_id', $userId);
        $newList->setAttribute('project_id', $projectId);
        $newList->save();

        return $newList;
    }

    public static function deleteProject(int $projectId): void
    {
        self::query()
            ->where([['project_id', '=', $projectId]])
            ->delete();
    }
    public static function deleteUserForProject(int $userId, int $projectId): void
    {
        self::query()
            ->where([['project_id', '=', $projectId], ['user_id', '=', $userId]])
            ->delete();
    }
}