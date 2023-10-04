<?php

namespace App\Controller\Files;

use App\Controller\UserController;
use App\Helpers\CheckTokenExpiration;
use App\Helpers\UploadFile;
use App\Models\AdditionalVideo;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;

class UploadAdditionalVideo extends UserController
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
                $data = $this->getFormData();
                $uploadedFile = $uploadedFiles['video'];

                if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
                    $filename = UploadFile::action(DIRECTORY_ADDITIONAL_VIDEO, $uploadedFile, $token->user_id);
                    $path = RELATIVE_PATH_ADDITIONAL_VIDEO . DIRECTORY_SEPARATOR . $filename;

                    $video = AdditionalVideo::addVideo($filename, $path, $data['time'], $token->user_id);

                    return $this->respondWithData(['path' => $video->path, 'id' => $video->id]);
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