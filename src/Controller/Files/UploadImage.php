<?php

namespace App\Controller\Files;

use App\Controller\UserController;
use App\Helpers\CheckTokenExpiration;
use App\Helpers\UploadFile;
use App\Models\ImageVideo;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;

class UploadImage extends UserController
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function action(): ResponseInterface
    {
        header('Access-Control-Allow-Origin: *');
        $access_token = $this->request->getHeaderLine('token');
        $token = JWT::decode($access_token, new Key($this->container->get('jwt-secret'), 'HS256'));

        if (CheckTokenExpiration::action($this->container->get('jwt-secret'), $access_token)) {

            try {

                $uploadedFiles = $this->request->getUploadedFiles();
                $uploadedFile = $uploadedFiles['image'];

                if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
                    $filename = UploadFile::action(DIRECTORY_IMG, $uploadedFile, $token->user_id);
                    $path = RELATIVE_PATH_IMG . DIRECTORY_SEPARATOR . $filename;

                    $image = ImageVideo::addImage($filename, $path, $token->user_id);

                    return $this->respondWithData($image->path);
                } else {
                    return $this->respondWithData($uploadedFile->getError(), 400);
                }

            } catch (Exception $e) {
                return $this->respondWithError($e->getCode(), $e->getMessage());
            }
        } else {
            return $this->respondWithError(215);
        }
    }
}