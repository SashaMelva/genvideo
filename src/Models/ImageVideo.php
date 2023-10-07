<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class ImageVideo extends Model
{
    public mixed $errors;
    protected $primaryKey = 'id';
    protected $table = 'image';

    public static function findAllByProjectId(int $projectId): array
    {
        return self::query()->where([['project_id', '=', $projectId]])
            ->get()->toArray();
    }

    public static function findOne(int $id): Model|Collection|Builder|array|null
    {
        return self::query()->find($id)->getModel();
    }

    public static function addImage(string $nameFile, string $path, int $projectId, ?string $type = null): ImageVideo
    {
        $newImage = new ImageVideo();
        $newImage->setAttribute('file_name', $nameFile);
        $newImage->setAttribute('file_path', $path);
        $newImage->setAttribute('project_id', $projectId);
        $newImage->setAttribute('type', $type);

        $newImage->save();

        return $newImage;
    }

    public static function deleteImage(int $imageId): void
    {
        self::query()
            ->where([['id', '=', $imageId]])
            ->delete();
    }
}