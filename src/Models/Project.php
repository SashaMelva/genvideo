<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;

class Project extends Model
{
    public mixed $errors;
    protected $primaryKey = 'id';
    protected $table = 'projects';


    public static function findOne(int $id): Model|Collection|Builder|array|null
    {
        return self::query()->find($id)->getModel();
    }

    public static function findByUserId(int $id): Model|Builder|null
    {
        return self::query()
            ->where([['id', '=', $id]])
            ->first();
    }

    public static function findAll(): array
    {
        return self::query()
            ->get()->toArray();
    }

    public static function accessCheckCreator(int $projectId, int $userid): bool
    {
        $access = self::query()
            ->where([['id', '=', $projectId]])
            ->get()->toArray()[0];

        return $access['creator_id'] == $userid;
    }

    public static function fullInfo(int $id): array
    {
        return self::query()
            ->select([
                'projects.id AS project_id',
                'projects.name AS project_name',
                'projects.creator_id',
                'projects.created_at AS project_created_at',
                'projects.updated_at AS project_updated_at',
                'users.name AS creator_name',
                'users.email AS creator_email',
                'users.role AS creator_role',
                'users.phone AS creator_phone',
            ])
            ->leftJoin('users', 'users.id', '=', 'projects.creator_id')
            ->where([['projects.id', '=', $id]])
            ->get()->toArray()[0];
    }

    public static function fullContent(int $id): array
    {
        return self::query()
            ->select([
                'projects.id AS project_id',
                'projects.name AS project_name',
                'projects.creator_id',
                'projects.created_at AS project_created_at',
                'projects.updated_at AS project_updated_at',
                'users.name AS creator_name',
                'users.email AS creator_email',
                'users.role AS creator_role',
                'users.phone AS creator_phone',
            ])
            ->leftJoin('users', 'users.id', '=', 'projects.creator_id')
            ->where([['projects.id', '=', $id]])
            ->get()->toArray();
    }

    public static function updateName(int $id, string $name): void
    {
        self::query()
            ->where([['id', '=', $id]])
            ->update(['name' => $name]);
    }

    public function validate(): bool
    {
        $validator = Validation::createValidator();

        $constraint = new Assert\Collection([
            'name' => new Assert\NotBlank(['message' => 'Не заполнено название проекта']),
            'creator_id' => new Assert\NotBlank(),
            'created_at' => new Assert\NotBlank(),
            'updated_at' => new Assert\NotBlank(),
        ]);

        $violations = $validator->validate($this->toArray(), $constraint);

        if (0 !== count($violations)) {
            foreach ($violations as $violation) {
                $key = str_replace(['[', ']'], '', $violation->getPropertyPath());
                $this->errors[$key] = $violation->getMessage();
            }
            return false;
        }

        return true;
    }

    public function getValidationErrors(): mixed
    {
        return $this->errors;
    }
}