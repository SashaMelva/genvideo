<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class GPTChatRequests extends Model
{
    public mixed $errors;
    protected $primaryKey = 'id';
    protected $table = 'GPT_chat_requests';

    public static function findByContentId(int $contentId): array
    {
        return self::query()
            ->where([['content_id', '=', $contentId]])
            ->get()->toArray();
    }

    public static function findOne(int $id): Model|Collection|Builder|array|null
    {
        return self::query()->find($id)->getModel();
    }

    public static function changeStatusAndError(int $requestId, int $statusId, string $text): void
    {
        self::query()
            ->where([['id', '=', $requestId]])
            ->update(['text_error' => $text, 'status_working' => $statusId]);
    }

    public static function changeStatusAndContent(int $requestId, int $statusId, string $text): void
    {
        self::query()
            ->where([['id', '=', $requestId]])
            ->update(['response' => $text, 'status_working' => $statusId]);
    }

    public static function changeStatus(int $requestId, int $statusId): void
    {
        self::query()
            ->where([['id', '=', $requestId]])
            ->update(['status_working' => $statusId]);
    }

    public static function changeStatusError(int $requestId, int $statusId, string $textError): void
    {
        self::query()
            ->where([['id', '=', $requestId]])
            ->update(['text_error' => $textError, 'status' => $statusId]);
    }
}