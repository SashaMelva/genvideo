<?php

namespace App\Controller\Users;

use App\Controller\UserController;
use App\Helpers\CreateToken;
use App\Models\User;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Header\Headers;
use Throwable;

class ChangePasswordViaMail  extends UserController
{
    public function action(): ResponseInterface
    {
        $data = json_decode($this->request->getBody()->getContents(), true);
        $user = User::findByUseremail($data['email']);

        if (empty($data['email']))
            return $this->respondWithError(400, ['email' => 'Необходимо заполнить поле Email']);

        if (is_null($user))
            return $this->respondWithError(400, ['email' => 'Пользователь с таким Email не зарегистрирован']);

        try {

            $smtp_config = $this->container->get('smtp_config');
            $token = CreateToken::action($user['id'], $this->container->get('jwt-secret'), $user['role'], true);

            $html = '<p>Здравствуйте!</p>' .
                '<p>Для изменения пароля перейдите по ссылке: <a href="'
                .'https://'.$_ENV['HOST'].'/restore?token='.$token.'">Изменить пароль</a>';

            $headers=new Headers();
            $headers->addHeader('List-Unsubscribe-Post','List-Unsubscribe=One-Click');
            $headers->addHeader('List-Unsubscribe','<https://solarmora.com/unsubscribe/example>');
            $headers->addIdHeader('Message-Id','1256632@smtp-pulse.com');
            $email = (new Email())
                ->setHeaders($headers)
                ->from(new Address($smtp_config['sender_email']))
                ->to($user['email'])
                ->subject('Смена пароля')
                ->html($html);

            $this->mailer->send($email);
            return $this->respondWithData('Success');

        } catch (Throwable $e) {
            return $this->respondWithError($e->getCode(), $e->getMessage());
        }
    }
}