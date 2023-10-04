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

    public static function findAllByUserId(int $userId): array
    {
        return self::query()->where([['user_id', '=', $userId]])
            ->get()->toArray();
    }

    public static function findOne(int $id): Model|Collection|Builder|array|null
    {
        return self::query()->find($id)->getModel();
    }

    public static function addImage(string $nameFile, string $path, int $userId, ?string $type = null): ImageVideo
    {
        $newImage = new ImageVideo();
        $newImage->setAttribute('name', $nameFile);
        $newImage->setAttribute('path', $path);
        $newImage->setAttribute('user_id', $userId);
        $newImage->setAttribute('type', $type);

        return $newImage;
    }

    public static function deleteImage(int $imageId): void
    {
        self::query()
            ->where([['id', '=', $imageId]])
            ->delete();
    }
}