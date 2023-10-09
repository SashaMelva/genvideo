<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class ColorBackground extends Model
{
    public mixed $errors;
    protected $primaryKey = 'id';
    protected $table = 'color_background';

    public static function findAll(): array
    {
        return self::query()->get()->toArray();
    }

    public static function findById(int $colorBackground): array
    {
        return self::query()
            ->where('id', '=', $colorBackground)
            ->get()->toArray()[0];
    }
}