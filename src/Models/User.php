<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints as Assert;

class User extends Model
{
    public mixed $errors;
    protected $primaryKey = 'id';
    protected $table = 'users';


    /**
     * @param int $user_id
     * @param int $school_id
     * @return bool
     * Проверка пользователя на право
     * доступа к данной школе
     */
    public static function accessCheck(int $user_id): bool
    {
        $access = self::query()
            ->select(['id'])
            ->where([['id', '=', $user_id]])
            ->get()->toArray();

        if (count($access) == 1) return true;
        return false;
    }

    public static function findOne(int $id): Model|Collection|Builder|array|null
    {
        return self::query()->find($id)->getModel();
    }

    public static function findAll(): array
    {
        return self::query()
            ->get()->toArray();
    }

    /**
     * @param $email
     * @return Model|Builder|null
     * Проверка наличия пользователя по Email
     */
    public static function findByUserEmail($email): Model|Builder|null
    {
        return self::query()
            ->where([['email', '=', $email]])
            ->first();
    }

    /**
     * @param int $id
     * @return Model|Builder|null
     * Проверка наличия пользователя по id
     */
    public static function findByUserId(int $id): Model|Builder|null
    {
        return self::query()
            ->where([['id', '=', $id]])
            ->first();
    }

    public function verifyPassword(string $password): bool
    {
        if (!password_verify($password, $this->getAttributeValue('password_hash'))) {
            return false;
        } else {
            return true;
        }
    }

    public function validate(): bool
    {
        $validator = Validation::createValidator();

        $constraint = new Assert\Collection([
            'login' => new Assert\NotBlank(['message' => 'Не заполнено поле Логин']),
            'email' => [
                new Assert\NotBlank(['message' => 'Не заполнено поле Email']),
                new Assert\Email(['message' => 'Значение {{ value }} не является правильным email адресом'])
            ],
            'phone' => new Assert\NotBlank(['message' => 'Не заполнено поле Телефон']),
            'role' => new Assert\NotBlank(),
            'password_hash' => new Assert\NotBlank(),
            'created_at' => new Assert\NotBlank(),
            'password' => new Assert\NotBlank(['message' => 'Не заполнено поле Пароль']),
            'status' => new Assert\Type(['type' => 'boolean'])
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

    public function validatePassword($data): bool
    {
        $validator = Validation::createValidator();

        $constraint = new Assert\Collection([
            'password' => new Assert\NotBlank(['message' => 'Не заполнено поле Старый пароль']),
            'new_password' => new Assert\NotBlank(['message' => 'Не заполнено поле Новый пароль']),
            'new_password2' => new Assert\NotBlank(['message' => 'Не заполнено поле Новый пароль (еще раз)']),
        ]);

        $violations = $validator->validate($data, $constraint);

        if (0 !== count($violations)) {
            foreach ($violations as $violation) {
                $key = str_replace(['[', ']'], '', $violation->getPropertyPath());
                $this->errors[$key] = $violation->getMessage();
            }
            return false;
        }

        return true;
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

    public function isAdmin(): bool
    {
        return $this->role == 'admin';
    }

    public function isUser(): bool
    {
        return $this->role == 'user';
    }

    /**
     * @return mixed
     */
    public function getValidationErrors(): mixed
    {
        return $this->errors;
    }
}