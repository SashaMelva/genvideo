<?php

namespace App\Helpers;

use Firebase\JWT\JWT;

class CreateToken
{
    public static function action(int $user_id, string $secret, string $role, bool $type = false): string
    {
        $conf = [
            'iss' => 'analytics',
            'aud' => 'analytics',
            'iat' => time(),
            'exp' => time() + 31536000,
            'user_id' => $user_id,
            'user_role' => $role,
        ];

        return JWT::encode($conf, $secret);
    }
}