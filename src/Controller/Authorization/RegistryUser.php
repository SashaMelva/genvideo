<?php

namespace App\Controller\Authorization;

use App\Controller\UserController;
use App\Helpers\CreateRefreshToken;
use App\Helpers\CreateToken;
use App\Helpers\DecodeToken;
use App\Helpers\SendEmail;
use App\Models\User;
use Exception;
use Illuminate\Database\QueryException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class RegistryUser  extends UserController
{
    public function action(): ResponseInterface
    {
        $data = json_decode($this->request->getBody()->getContents(), true);

        if ($this->checkOfParameters($data) === false)
            return $this->respondWithError(400, ['message' => 'Не все параметры переданы']);

        if (!$data['agreed'])
            return $this->respondWithError(400, ['agreed' => 'Не подтверждено соглашение']);

        if (!is_null(User::findByUserEmail($data['email'])))
            return $this->respondWithError(400, ['email' => 'Пользователь с таким Email уже зарегистрирован']);

        $cookies = $this->request->getCookieParams();

        try {

            $user = new User();
            $user->setAttribute('name', $data['name']);
            $user->setAttribute('email', mb_strtolower($data['email']));
            $user->setAttribute('phone', $data['phone']);
            $user->setAttribute('password', $data['password']);
            $user->setAttribute('password_hash', password_hash($data['password'], PASSWORD_DEFAULT));
            $user->setAttribute('role', 'admin');
            $user->setAttribute('status', true);
            $user->setAttribute('created_at', new \DateTimeImmutable());

            if ($user->validate()) {
                try {
                    $user->save();

                    $token = CreateToken::action($user['id'], $this->container->get('jwt-secret'), 'admin');
                    $refreshToken = CreateRefreshToken::action($user['id'], $this->container->get('jwt-secret'), 'admin', $data['fingerprint']);
                    $token_info = DecodeToken::action($this->container->get('jwt-secret'), $refreshToken);
                    SendEmail::action($user, $this->container->get('smtp_config'), $this->mailer);

                } catch (QueryException | Exception | NotFoundExceptionInterface | ContainerExceptionInterface $e) {
                    return $this->respondWithError($e->getCode(), $e->getMessage());
                }
            } else {
                return $this->respondWithError(400, $user->getValidationErrors());
            }

            $expires = $token_info['exp'];
            $cookie[] = "refreshToken=$refreshToken; path=/api/auth; domain=.{$_ENV['HOST']}; maxAge=$expires; expires=$expires; HttpOnly";
            return $this->respondWithData(['access_token' => $token, 'refresh_token' => $refreshToken], 200, $cookie);

        } catch (Throwable $e) {
            return $this->respondWithError($e->getCode(), $e->getMessage());
        }
    }
}