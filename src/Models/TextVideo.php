<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class TextVideo extends Model
{
    public mixed $errors;
    protected $primaryKey = 'id';
    protected $table = 'text';

    public static function findOne(int $id): Model|Collection|Builder|array|null
    {
        return self::query()->find($id)->getModel();
    }

    public static function findAllByContentId(int $contentId): array
    {
        return self::query()->where([['content_id', '=', $contentId]])
            ->get()->toArray();
    }
    public static function addText(int $projectId, string $text, string $fileName, string $filePath): TextVideo
    {
        $textVideo = new TextVideo();
        $textVideo->setAttribute('project_id', $projectId);
        $textVideo->setAttribute('text', $text);
        $textVideo->setAttribute('created_at', new \DateTimeImmutable());
        $textVideo->setAttribute('status', 'Не озвучено');
        $textVideo->setAttribute('file_name', $fileName);
        $textVideo->setAttribute('file_path', $filePath);

        return $textVideo;
    }

    public static function deleteText($textId): void
    {
        self::query()
            ->where([['id', '=', $textId]])
            ->delete();
    }
}