<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class Voice  extends Model
{
    public mixed $errors;
    protected $primaryKey = 'id';
    protected $table = 'dictionary_voice';

    public static function getAllData()
    {
        return self::query()->get()->toArray();
    }

    public static function getBySpeakerName(string $nameSpeaker)
    {
        return self::query()
            ->where([['name', '=', $nameSpeaker]])
            ->get()->toArray();
    }
}