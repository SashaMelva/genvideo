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

    public static function findAllByUserId(int $userId): array
    {
        return self::query()->where([['user_id', '=', $userId]])
            ->get()->toArray();
    }

    public static function findOne(int $id): Model|Collection|Builder|array|null
    {
        return self::query()->find($id)->getModel();
    }

    public static function addMusic($nameFile, $path, $time, $userId, $type = null): MusicVideo
    {
        $newMusic = new MusicVideo();
        $newMusic->setAttribute('name', $nameFile);
        $newMusic->setAttribute('path', $path);
        $newMusic->setAttribute('time', $time);
        $newMusic->setAttribute('user_id', $userId);
        $newMusic->setAttribute('type', $type);

        return $newMusic;
    }

    public static function deleteMusic($musicId): void
    {
        self::query()
            ->where([['id', '=', $musicId]])
            ->delete();
    }
}