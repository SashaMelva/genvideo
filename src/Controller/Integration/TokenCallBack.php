<?php

namespace App\Controller\Integration;

use App\Controller\UserController;
use Google\Client;
use Google_Service_YouTube;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class TokenCallBack extends UserController
{
    public function action(): ResponseInterface
    {
        try {

            $userId = $this->request->getAttribute('id');

            $client = new Client();
            $client->setAuthConfigFile(DIRECTORY_CLIENT_SECRET . 'client_secret.json');
            $client->setRedirectUri('http://localhost:8080/api/token-callback/' . $userId);
            $client->addScope(GOOGLE_SERVICE_YOUTUBE::YOUTUBE_FORCE_SSL);

            if (!isset($_GET['code'])) {
                $auth_url = $client->createAuthUrl();
                return $this->response->withHeader('Location', filter_var($auth_url, FILTER_SANITIZE_URL));
            } else {
                $client->authenticate($_GET['code']);
                $_SESSION['access_token'] = $client->getAccessToken();
                $redirect_uri = 'http://localhost:8080/api/add-token/' . $userId;
                return $this->response->withHeader('Location', filter_var($redirect_uri, FILTER_SANITIZE_URL));
            }

        } catch (Throwable $e) {
            return $this->respondWithError($e->getCode(), $e->getMessage());
        }
    }
}