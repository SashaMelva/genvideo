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

    public static function addContent(
        string $name,
        int $userId,
        ?string $filePath,
        ?string $fileName,
        int $projectId,
        string $typeBackground,
        int $voiceId,
        string $format,
        string $colorBackground,
        string $statusId,
        string $textId,
        string $ampulaVoice
    ): ContentVideo
    {
        $newContent = new ContentVideo();
        $newContent->setAttribute('name', $name);
        $newContent->setAttribute('creator_id', $userId);
        $newContent->setAttribute('created_at', new \DateTimeImmutable());
        $newContent->setAttribute('file_path', $filePath);
        $newContent->setAttribute('updated_at', new \DateTimeImmutable());
        $newContent->setAttribute('file_name', $fileName);
        $newContent->setAttribute('project_id', $projectId);
        $newContent->setAttribute('type_background', $typeBackground);
        $newContent->setAttribute('voice_id', $voiceId);
        $newContent->setAttribute('format', $format);
        $newContent->setAttribute('color_background', $colorBackground);
        $newContent->setAttribute('status_id', $statusId);
        $newContent->setAttribute('text_id', $textId);
        $newContent->setAttribute('ampula_voice', $ampulaVoice);

        $newContent->save();
        return $newContent;
    }

    public static function findAllDataByID(int $id): array
    {
        return self::query()
            ->select(
                'content.id AS content_id',
                'content.name AS content_name',
                'content.creator_id AS content_creator_id',
                'content.type_background AS content_type_background',
                'content.voice_id AS content_voice_id',
                'content.format AS content_format',
                'content.color_background AS content_color_background',
                'content.status AS content_status',
            )
            ->leftJoin('text', 'text.id', '=', 'projects.creator_id')
            ->leftJoin('users', 'users.id', '=', 'projects.creator_id')
            ->leftJoin('users', 'users.id', '=', 'projects.creator_id')
            ->where([['content.id', '=', $id]])
            ->get()->toArray()[0];
    }

    public static function deleteContent(int $contentId): void
    {
        self::query()
            ->where([['id', '=', $contentId]])
            ->delete();
    }
}