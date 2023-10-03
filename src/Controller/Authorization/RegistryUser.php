<?php

namespace App\Controller\Authorization;

use App\Controller\UserController;
use App\Helpers\CreateRefreshToken;
use App\Helpers\CreateToken;
use App\Helpers\DecodeToken;
use App\Helpers\SendEmail;
use App\Models\User;
use App\Models\UsersPartnerReferals;
use Exception;
use Illuminate\Database\QueryException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;
use function Sentry\captureException;

class RegistryUser  extends UserController
{
    public function action(): ResponseInterface
    {
        $data = $this->getFormData();

        if ($this->checkOfParameters($data) === false)
            return $this->respondWithError(400, ['msg' => 'Не все параметры переданы']);

        if (!is_null(User::findByUserEmail($data['email'])))
            return $this->respondWithError(400, ['email' => 'Пользователь с таким Email уже зарегистрирован']);

        $cookies = $this->request->getCookieParams();

        try {

            $user = new User();
            $user->setAttribute('login', $data['login']);
            $user->setAttribute('email', mb_strtolower($data['email']));
            $user->setAttribute('phone', $data['phone']);
            $user->setAttribute('password', $data['password']);
            $user->setAttribute('password_hash', password_hash($data['password'], PASSWORD_DEFAULT));
            $user->setAttribute('role', 'user');
            $user->setAttribute('status', true);
            $user->setAttribute('created_at', new \DateTimeImmutable());

            if ($user->validate()) {
                try {

                    $partner_status = false;
                    if (array_key_exists('_scan_fr', $cookies) && is_numeric($cookies['_scan_fr']) && mb_strlen($cookies['_scan_fr']) <= 10) {
                        if (!is_null(User::findByUserId((int)$cookies['_scan_fr']))) {
                            $user->setAttribute('partner_id', $cookies['_scan_fr']);
                            $partner_status = true;
                        }
                    }

                    if (!$partner_status) {
                        if ($data['fr'] != '-' && is_numeric($data['fr']) && mb_strlen($data['fr']) <= 10) {
                            if (!is_null(User::findByUserId((int)$data['fr']))) {
                                $user->setAttribute('partner_id', $data['fr']);
                                $partner_status = true;
                            }
                        }
                    }

                    $user->save();

                    $token = CreateToken::action($user['id'], $this->container->get('jwt-secret'), 'admin');
                    $refrech_token = CreateRefreshToken::action($user['id'], $this->container->get('jwt-secret'), 'admin', $data['fingerprint']);
                    $token_info = DecodeToken::action($this->container->get('jwt-secret'), $refrech_token);

                } catch (QueryException | Exception | NotFoundExceptionInterface | ContainerExceptionInterface $e) {
                    return $this->respondWithError($e->getCode(), $e->getMessage());
                }
            } else {
                return $this->respondWithError(400, $user->getValidationErrors());
            }

            $expires = $token_info['exp'];
            $cookie[] = "refreshToken=$refrech_token; path=/api/auth; domain=.{$_ENV['HOST']}; maxAge=$expires; expires=$expires; HttpOnly";
            return $this->respondWithData(['access_token' => $token], 200, $cookie);

        } catch (Throwable $e) {
            return $this->respondWithError($e->getCode(), $e->getMessage());
        }
    }
}