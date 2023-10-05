<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;

class Project  extends Model
{
    public mixed $errors;
    protected $primaryKey = 'id';
    protected $table = 'projects';


    public static function findOne(int $id): Model|Collection|Builder|array|null
    {
        return self::query()->find($id)->getModel();
    }

    public static function findAll(): array
    {
        return self::query()
            ->get()->toArray();
    }

    public static function findByUserEmail($email): Model|Builder|null
    {
        return self::query()
            ->where([['email', '=', $email]])
            ->first();
    }

    public static function findByUserId(int $id): Model|Builder|null
    {
        return self::query()
            ->where([['id', '=', $id]])
            ->first();
    }

    public static function createUser($userName, $userEmail, $password, $role = 'user'): User
    {
        $newUser = new User();
        $newUser->setAttribute('name', $userName);
        $newUser->setAttribute('email', $userEmail);
        $newUser->setAttribute('password_hash', password_hash($password, PASSWORD_DEFAULT));
        $newUser->setAttribute('role', $role);

        return $newUser;
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