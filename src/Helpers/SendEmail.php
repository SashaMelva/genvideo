<?php

namespace App\Helpers;

use App\Models\User;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class SendEmail
{
    /**
     * @throws TransportExceptionInterface
     */
    public static function action(User $user, array $smtp_config, Mailer $mailer)
    {
        $html = '<p>Здравствуйте!</p>' .
            '<p>Данные для входа в сервис генерации видео:</p>' .
            '<p>https://'.$_ENV['HOST'].'/login</p>' .
            '<p>Логин: ' . $user['email'] . '</p>' .
            '<p>Пароль: ' . $user['password'] . '</p>';

        $email = (new Email())
            ->from(new Address($smtp_config['sender_email']))
            ->to($user->getAttributeValue('email'))
            ->subject('Доступ к сервису')
            ->html($html);

        $mailer->send($email);

    }
}