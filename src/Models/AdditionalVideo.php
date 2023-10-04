<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class AdditionalVideo extends Model
{
    public mixed $errors;
    protected $primaryKey = 'id';
    protected $table = 'additional_video';

    public static function findAllByUserId(int $userId): array
    {
        return self::query()->where([['user_id', '=', $userId]])
            ->get()->toArray();
    }

    public static function findOne(int $id): Model|Collection|Builder|array|null
    {
        return self::query()->find($id)->getModel();
    }

    public static function addVideo(string $nameFile, string $path, string $time, int $userId, ?string $type = null): AdditionalVideo
    {
        $newVideo = new AdditionalVideo();
        $newVideo->setAttribute('name', $nameFile);
        $newVideo->setAttribute('path', $path);
        $newVideo->setAttribute('time', $time);
        $newVideo->setAttribute('user_id', $userId);
        $newVideo->setAttribute('type', $type);

        return $newVideo;
    }

    public static function deleteVideo($videoId): void
    {
        self::query()
            ->where([['id', '=', $videoId]])
            ->delete();
    }
}