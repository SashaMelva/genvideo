<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ListMusic  extends Model
{
    public mixed $errors;
    protected $primaryKey = 'id';
    protected $table = 'list_music';

    public static function findAllByMusicId(int $musicId): array
    {
        return self::query()->where([['music_id', '=', $musicId]])
            ->get()->toArray();
    }

    public static function findAllByContentId(int $contentId): array
    {
        return self::query()->where([['content_id', '=', $contentId]])
            ->get()->toArray();
    }

    public static function addMusic(int $musicId, int $contentId): ListMusic
    {
        $newList = new ListMusic();
        $newList->setAttribute('music_id', $musicId);
        $newList->setAttribute('content_id', $contentId);

        return $newList;
    }

    public static function deleteMusic($musicId): void
    {
        self::query()
            ->where([['music_id', '=', $musicId]])
            ->delete();
    }
}