<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ListVideo extends Model
{
    public mixed $errors;
    protected $primaryKey = 'id';
    protected $table = 'list_additional_video';

    public static function findAllByVideoId(int $videoId): array
    {
        return self::query()->where([['video_id', '=', $videoId]])
            ->get()->toArray();
    }

    public static function findAllByContentId(int $contentId): array
    {
        return self::query()->where([['content_id', '=', $contentId]])
            ->get()->toArray();
    }

    public static function addVideo(int $videoId, int $contentId): ListVideo
    {
        $newList = new ListVideo();
        $newList->setAttribute('video_id', $videoId);
        $newList->setAttribute('content_id', $contentId);
        $newList->save();
        return $newList;
    }

    public static function deleteVideo($videoId): void
    {
        self::query()
            ->where([['video_id', '=', $videoId]])
            ->delete();
    }
}