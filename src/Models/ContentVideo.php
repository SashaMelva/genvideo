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

    public static function countContent(int $projectId): int
    {
        return self::query()->where([['project_id', '=', $projectId]])->count();
    }

    public static function findOne(int $id): Model|Collection|Builder|array|null
    {
        return self::query()->find($id)->getModel();
    }

    public static function findByList(int $id, int $skip, int $take, string $sort): array
    {
        return self::query()
            ->select(
                'content.id AS content_id',
                'content.name AS content_name',
                'content.created_at',
                'content.updated_at',
                'content.file_path',
                'content.file_name',
                'content.project_id',
//                'content.format',
                'content.status_id',
                'status_content.name AS status_name',
//                'users.id AS creator_id',
//                'users.name AS creator_name',
//                'users.email AS creator_email',
            )
            ->leftJoin('status_content', 'content.status_id', '=', 'status_content.id')
//            ->leftJoin('users', 'content.creator_id', '=', 'users.id')
            ->where([['content.project_id', '=', $id]])
            ->orderBy('content.created_at', $sort ? 'ASC' : 'DESC')
            ->orderBy('content.id')
            ->skip($skip)->take($take)
            ->get()->toArray();
    }

    public static function findByID(int $id): array
    {
        return self::query()
            ->select(
                'content.id AS content_id',
                'content.name AS content_name',
                'content.created_at',
                'content.updated_at',
                'content.file_path',
                'content.file_name',
                'content.project_id',
                'content.format',
                'content.preview_text',
                'content.preview_file_name',
                'content.status_id',
                'status_content.name AS status_name',
                'users.id AS creator_id',
                'users.name AS creator_name',
                'users.email AS creator_email',
            )
            ->leftJoin('status_content', 'content.status_id', '=', 'status_content.id')
            ->leftJoin('users', 'content.creator_id', '=', 'users.id')
            ->where([['content.id', '=', $id]])
            ->get()->toArray()[0];
    }

    public static function findAllDataByID(int $id): array
    {
        return self::query()
            ->select(
                'content.id AS content_id',
                'content.name AS content_name',
                'content.creator_id AS content_creator_id',
                'content.type_background',
                'content.voice_id',
                'content.ampula_voice',
                'content.format AS content_format',
                'content.color_background_id AS color_background_id',
                'content.preview_text',
                'content.delay_end_video',
                'text.id AS text_id',
                'text.text',
                'text.file_name_text',
                'text.file_path_text',
                'text.file_name_voice',
                'text.file_path_voice',
                'text.status_voice',
                'text.status_text',
                'text.delay_between_offers',
                'text.delay_between_paragraphs',
                'text.end_text',
                'text.initial_text',
                'text.voice_speed',
                'text.subtitles',
                'status_content.name AS status_content_name',
                'dictionary_voice.name AS dictionary_voice_name',
                'dictionary_voice.language',
                'color_background_id'
            )
            ->leftJoin('text', 'content.text_id', '=', 'text.id')
            ->leftJoin('status_content', 'content.status_id', '=', 'status_content.id')
            ->leftJoin('dictionary_voice', 'content.voice_id', '=', 'dictionary_voice.id')
            ->where([['content.id', '=', $id]])
            ->get()->toArray()[0];
    }

    public static function changeStatus(int $contentId, string $statusId): void
    {
        self::query()
            ->where([['id', '=', $contentId]])
            ->update(['status_id' => $statusId]);
    }

    public static function addContent(
        string  $name,
        int     $userId,
        ?string $filePath,
        ?string $fileName,
        int     $projectId,
        string  $typeBackground,
        int     $voiceId,
        string  $format,
        ?int    $colorBackgroundId,
        string  $statusId,
        string  $textId,
        string  $ampulaVoice,
        ?int  $statusGeneratorImage = 0,
        ?string $delayEndVideo = '3',
        ?string $preview = '',
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
        $newContent->setAttribute('color_background_id', $colorBackgroundId);
        $newContent->setAttribute('status_id', $statusId);
        $newContent->setAttribute('text_id', $textId);
        $newContent->setAttribute('ampula_voice', $ampulaVoice);
        $newContent->setAttribute('genertor_image_status', $statusGeneratorImage);
        $newContent->setAttribute('delay_end_video', $delayEndVideo);
        $newContent->setAttribute('preview_text', $preview);
        $newContent->save();
        return $newContent;
    }

    public static function updateContent(int $contentId, string $fileName, string $filePath, int $statusId): void
    {
        self::query()
            ->where([['id', '=', $contentId]])
            ->update(['file_path' => $filePath, 'file_name' => $fileName, 'status_id' => $statusId]);
    }

    public static function deleteContent(int $contentId): void
    {
        self::query()
            ->where([['id', '=', $contentId]])
            ->delete();
    }

    public static function changePreview(int $videoId, mixed $previewName): void
    {
        self::query()
            ->where([['id', '=', $videoId]])
            ->update(['preview_file_name' => $previewName]);
    }
}