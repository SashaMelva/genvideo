<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TokenYoutube extends Model
{
    public mixed $errors;
    protected $primaryKey = 'id';
    protected $table = 'token_youtube';

    public static function getAllData(): array
    {
        return self::query()->get()->toArray();
    }

    public static function getTokenByUserId(string $userId): array
    {
        return  self::query()
            ->where([['user_id', '=', $userId]])
            ->get()->toArray();
    }

    public static function addToken(int $userId, string $accessToken, string $refreshToken): TokenYoutube
    {
        $newVideo = new TokenYoutube();
        $newVideo->setAttribute('user_id', $userId);
        $newVideo->setAttribute('access_token', $accessToken);
        $newVideo->setAttribute('refresh_token', $refreshToken);
        $newVideo->save();

        return $newVideo;
    }

    public static function updateToken(int $userId, string $accessToken, string $refreshToken): void
    {
        self::query()
            ->where([['user_id', '=', $userId]])
            ->update(['access_token' => $accessToken, 'refresh_token' => $refreshToken]);
    }

    public static function checkToken(int $userId): bool
    {
        $access = self::query()
            ->select(['id'])
            ->where([['user_id', '=', $userId]])
            ->get()->toArray();

        if (count($access) == 1) return true;
        return false;
    }

    public static function deleteToken(int $userId): void
    {
        self::query()
            ->where([['user_id', '=', $userId]])
            ->delete();
    }
}