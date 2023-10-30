<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class MusicVideo extends Model
{
    public mixed $errors;
    protected $primaryKey = 'id';
    protected $table = 'music';

    public static function findAllByProjectId(int $projectId): array
    {
        return self::query()
            ->where([['project_id', '=', $projectId]])
            ->orWhere([['project_id', '=', 8]])
            ->get()->toArray();
    }

    public static function findAll(): array
    {
        return self::query()
            ->get()->toArray();
    }

    public static function findOne(int $id): Model|Collection|Builder|array|null
    {
        return self::query()->find($id)->getModel();
    }

    public static function addMusic(string $filename, string $path, string $time, int $userId, ?string $name, ?string $type = null): MusicVideo
    {
        $newMusic = new MusicVideo();
        $newMusic->setAttribute('file_name', $filename);
        $newMusic->setAttribute('file_path', $path);
        $newMusic->setAttribute('time', $time);
        $newMusic->setAttribute('project_id', $userId);
        $newMusic->setAttribute('type', $type);
        $newMusic->setAttribute('name', $name);
        $newMusic->save();

        return $newMusic;
    }

    public static function deleteMusic($musicId): void
    {
        self::query()
            ->where([['id', '=', $musicId]])
            ->delete();
    }
}