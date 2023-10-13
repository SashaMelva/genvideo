<?php

namespace App\Controller\Integration;

use App\Controller\UserController;
use Google\Client;
use Google_Service_YouTube;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class GetToken extends UserController
{
    public function action(): ResponseInterface
    {
        try {

            $userId = $this->request->getAttribute('id');
            $_SESSION['user_id'] = $userId;
            $client = new Client();
            $client->setAuthConfig(DIRECTORY_CLIENT_SECRET . 'client_secret.json');
            $client->addScope(GOOGLE_SERVICE_YOUTUBE::YOUTUBE_FORCE_SSL);
            $client->setAccessType('offline');

            if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {

                $client->setAccessToken($_SESSION['access_token']);
                $redirect_uri = 'http://localhost:8080/api/add-token/' . $userId;
                return $this->response->withHeader('Location', filter_var($redirect_uri, FILTER_SANITIZE_URL));

            } else {

                $redirect_uri = 'http://localhost:8080/api/token-callback';
                return $this->response->withHeader('Location', filter_var($redirect_uri, FILTER_SANITIZE_URL));

            }

        } catch (Throwable $e) {
            return $this->respondWithError($e->getCode(), $e->getMessage());
        }
    }
}