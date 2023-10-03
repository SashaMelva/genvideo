<?php

namespace App\Helpers;

use DateTime;
use Exception;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class CheckTokenExpiration
{
    public static function action(string $secret, string $token): float|bool|int
    {
        try {
            $decoded_token = JWT::decode($token, new Key($secret,'HS256'));
        } catch (ExpiredException | Exception){
            return false;
        }

        $now = new DateTime();
        $exp = new DateTime();
        $exp->setTimestamp($decoded_token->exp);

        $diff = date_diff($now, $exp);
        return $diff->s+$diff->days*24*60*60+$diff->h*60*60+$diff->i*60;
    }
}