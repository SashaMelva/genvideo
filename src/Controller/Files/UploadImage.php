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
        $data = $this->getFormData();
        $access_token = $this->request->getHeaderLine('token');
        $token = JWT::decode($access_token, new Key($this->container->get('jwt-secret'), 'HS256'));

        if (CheckTokenExpiration::action($this->container->get('jwt-secret'), $access_token)) {

            try {

                $uploadedFiles = $this->request->getUploadedFiles();
                $fileNameImage = $data['project_id'] . '_' . date('Y_m_d_H_i_s') . '_' . floor(microtime(true) * 1000);
                $uploadedFile = $uploadedFiles['image'];

                if ($uploadedFile->getError() === UPLOAD_ERR_OK) {

                    if ($data['type_image'] == 'slide') {
                        $filename = UploadFile::action(DIRECTORY_IMG, $uploadedFile, $fileNameImage);
                        $filePath = RELATIVE_PATH_IMG . $filename;
                    }

                    if ($data['type_image'] == 'logo') {
                        $filename = UploadFile::action(DIRECTORY_LOGO_IMG, $uploadedFile, $fileNameImage);
                        $filePath = RELATIVE_PATH_LOGO_IMG . $filename;
                    }

                    if (empty($filename) || empty($filePath)) {
                        return $this->respondWithError(400, 'Ошибка загрузки изображения');
                    }

                    $image = ImageVideo::addImage($filename, $filePath, $data['project_id'], $data['type_image']);

                    return $this->respondWithData(['path' => $image->file_path, 'id' => $image->id]);
                } else {
                    return $this->respondWithError(400, 'Ошибка получения избражения. Код ошибки: ' . $uploadedFile->getError());
                }


            } catch (Exception $e) {
                return $this->respondWithError($e->getCode(), $e->getMessage());
            }
        } else {
            return $this->respondWithError(215);
        }
    }
}