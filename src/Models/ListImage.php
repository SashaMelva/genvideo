<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ListImage  extends Model
{
    public mixed $errors;
    protected $primaryKey = 'id';
    protected $table = 'list_image';

    public static function findAllByImageId(int $imageId): array
    {
        return self::query()->where([['image_id', '=', $imageId]])
            ->get()->toArray();
    }

    public static function findAllByContentId(int $contentId): array
    {
        return self::query()->where([['content_id', '=', $contentId]])
            ->get()->toArray();
    }

    public static function addImage(int $imageId, int $contentId): ListImage
    {
        $newList = new ListImage();
        $newList->setAttribute('image_id', $imageId);
        $newList->setAttribute('content_id', $contentId);

        return $newList;
    }

    public static function deleteImage($imageId): void
    {
        self::query()
            ->where([['image_id', '=', $imageId]])
            ->delete();
    }
}