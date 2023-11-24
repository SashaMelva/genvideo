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
        return self::query()
            ->select('content_id', 'image.id', 'image.file_name', 'image.file_path', 'image.type', 'image.project_id', 'image.name')
            ->leftJoin('image', 'list_image.image_id', 'image.id')
            ->where([['content_id', '=', $contentId]])
            ->get()->toArray();
    }

    public static function addImage(int $imageId, int $contentId): ListImage
    {
        $newList = new ListImage();
        $newList->setAttribute('image_id', $imageId);
        $newList->setAttribute('content_id', $contentId);
        $newList->save();
        return $newList;
    }

    public static function deleteImage($imageId): void
    {
        self::query()
            ->where([['image_id', '=', $imageId]])
            ->delete();
    }
}