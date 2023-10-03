<?php

namespace App\Helpers;

use Exception;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class DecodeToken
{
    public static function action(string $secret, string $token)
    {
        return json_decode(json_encode(
            JWT::decode($token, new Key($secret,'HS256'))
        ), true);
    }
}