<?php
namespace App\Helpers;

use App\Models\UserRefreshToken;
use Firebase\JWT\JWT;

class CreateRefreshToken
{
    public static function action(int $user_id, string $secret, string $role, string $fingerprint)
    {
        $conf = [
            "iss" => "analytics",
            "aud" => "analytics",
            "iat" => time(),
            "exp" => time() + 31536000,
            "user_id" => $user_id,
            'user_role' => $role,
            'fingerprint' => $fingerprint,
        ];

        $tokenCount = UserRefreshToken::query()
            ->where(['user_id' => $user_id])
            ->count();

        if ($tokenCount > 5) {
            UserRefreshToken::query()
                ->where([['user_id', '=', $user_id]])
                ->delete();
        }

        $user_token = new UserRefreshToken();
        $user_token->setAttribute('user_id', $user_id);
        $user_token->setAttribute('refresh_token', JWT::encode($conf, $secret));
        $user_token->setAttribute('expires_in', (new \DateTime)->setTimestamp($conf['exp']));
        $user_token->setAttribute('fingerprint', $fingerprint);
        $user_token->save();

        return $user_token->getAttributeValue('refresh_token');
    }
}