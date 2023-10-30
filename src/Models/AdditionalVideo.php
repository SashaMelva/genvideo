<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class AdditionalVideo extends Model
{
    public mixed $errors;
    protected $primaryKey = 'id';
    protected $table = 'video';

    public static function findAllByProjectId(int $projectId): array
    {
        return self::query()->where([['project_id', '=', $projectId]])
            ->get()->toArray();
    }

    public static function findOne(int $id): Model|Collection|Builder|array|null
    {
        return self::query()->find($id)->getModel();
    }

    public static function addVideo(string $fileName, string $filePath, string $time, int $projectId, ?string $name, ?string $type = null): AdditionalVideo
    {
        $newVideo = new AdditionalVideo();
        $newVideo->setAttribute('file_name', $fileName);
        $newVideo->setAttribute('file_path', $filePath);
        $newVideo->setAttribute('time', $time);
        $newVideo->setAttribute('project_id', $projectId);
        $newVideo->setAttribute('type', $type);
        $newVideo->setAttribute('name', $name);
        $newVideo->save();

        return $newVideo;
    }

    public static function deleteVideo(int $videoId): void
    {
        self::query()
            ->where([['id', '=', $videoId]])
            ->delete();
    }
}