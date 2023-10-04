<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class ContentVideo extends Model
{
    public mixed $errors;
    protected $primaryKey = 'id';
    protected $table = 'content';

    public static function findAllByUserId(int $userId): array
    {
        return self::query()->where([['user_id', '=', $userId]])
            ->get()->toArray();
    }

    public static function findOne(int $id): Model|Collection|Builder|array|null
    {
        return self::query()->find($id)->getModel();
    }

    public static function addContent(string $fileName, string $text, string $path, string $timeVideo, int $userId, ?string $type = null): ContentVideo
    {
        $newContent = new ContentVideo();
        $newContent->setAttribute('name', $fileName);
        $newContent->setAttribute('text', $text);
        $newContent->setAttribute('time_video', $timeVideo);
        $newContent->setAttribute('user_id', $userId);
        $newContent->setAttribute('path_video', $path);
        $newContent->setAttribute('created_at', $type);

        return $newContent;
    }

    public static function deleteContent(int $contentId): void
    {
        self::query()
            ->where([['id', '=', $contentId]])
            ->delete();
    }
}