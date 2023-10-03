<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class UserRefreshToken extends Model
{
    protected $table = 'user_refresh_token';
    protected $primaryKey = 'id';
    public $incrementing = false;

    public static function findByToken($token): Model|Builder|null
    {
        return self::query()
            ->where([['refresh_token', '=', $token]])
            ->first();
    }
}