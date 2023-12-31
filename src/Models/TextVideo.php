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

    public static function findById(int $id)
    {
        return self::query()->where([['id', '=', $id]])->get()->toArray();
    }

    public static function findByContentId(int $contentId): array
    {
        return self::query()->where([['content_id', '=', $contentId]])
            ->get()->toArray();
    }

    public static function addText(int $projectId, string $text, ?string $fileName, ?string $filePath, ?string $fileNameVoice, ?string $filePathVoice, bool $statusVoice, bool $statusText, ?string $timeVoice, string $subtitles): TextVideo
    {
        $textVideo = new TextVideo();
        $textVideo->setAttribute('project_id', $projectId);
        $textVideo->setAttribute('text', $text);
        $textVideo->setAttribute('created_at', new \DateTimeImmutable());
        $textVideo->setAttribute('updated_at', new \DateTimeImmutable());
        $textVideo->setAttribute('file_name_text', $fileName);
        $textVideo->setAttribute('file_path_text', $filePath);
        $textVideo->setAttribute('file_name_voice', $fileNameVoice);
        $textVideo->setAttribute('file_path_voice', $filePathVoice);
        $textVideo->setAttribute('status_voice', $statusVoice);
        $textVideo->setAttribute('status_text', $statusText);
        $textVideo->setAttribute('time_voice', $timeVoice);
        $textVideo->setAttribute('subtitles', $subtitles);

        $textVideo->save();
        return $textVideo;
    }

    public static function updateFileText(int $textId, string $fileName, string $filePath, bool $status): void
    {
        self::query()
            ->where([['id', '=', $textId]])
            ->update(['file_name_text' => $fileName, 'file_path_text' => $filePath, 'status_text' => $status]);
    }

    public static function changeTextStatus(int $textId, string $status): void
    {
        self::query()
            ->where([['id', '=', $textId]])
            ->update(['status_text' => $status]);
    }

    public static function updateFileTextAndStatus(int $textId, string $fileName, string $filePath, bool $status, string $fileNameVoice, string $filePathVoice, bool $statusVoice, string $timeVoice): void
    {
        self::query()
            ->where([['id', '=', $textId]])
            ->update(['file_name_text' => $fileName, 'file_path_text' => $filePath, 'status_text' => $status, 'file_name_voice' => $fileNameVoice, 'file_path_voice' => $filePathVoice, 'status_voice' => $statusVoice, 'time_voice' => $timeVoice]);
    }

    public static function changeVoiceStatus(int $textId, string $status): void
    {
        self::query()
            ->where([['id', '=', $textId]])
            ->update(['status_voice' => $status]);
    }



    public static function updatedContentData(int $textId, string $text): void
    {
        self::query()
            ->where([['id', '=', $textId]])
            ->update(['text' => $text]);
    }

    public static function deleteText(int $textId): void
    {
        self::query()
            ->where([['id', '=', $textId]])
            ->delete();
    }
}