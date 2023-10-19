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
        $data = $this->getFormData();
//        $access_token = $this->request->getHeaderLine('token');
//        $token = JWT::decode($access_token, new Key($this->container->get('jwt-secret'), 'HS256'));

//        if (CheckTokenExpiration::action($this->container->get('jwt-secret'), $access_token)) {

//            try {

                $uploadedFiles = $this->request->getUploadedFiles();
                $uploadedFile = $uploadedFiles['video'];
                $fileNameMusic = $data['project_id'] . '_' . date('Y_m_d_H_i_s') . '_' . floor(microtime(true) * 1000);

                if ($uploadedFile->getError() === UPLOAD_ERR_OK) {


                    $filename = UploadFile::action(DIRECTORY_ADDITIONAL_VIDEO, $uploadedFile, $fileNameMusic);
                    $filePath = RELATIVE_PATH_ADDITIONAL_VIDEO . $filename;

                    if (empty($filename) || empty($filePath)) {
                        return $this->respondWithError(400, 'Ошибка загрузки видео');
                    }

                    $video = AdditionalVideo::addVideo($filename, $filePath, '00:00', $data['project_id'], $data['type_video']);
                    return $this->respondWithData(['path' => $video->file_path, 'id' => $video->id]);

                } else {
                    return $this->respondWithError(400, 'Ошибка получения видео. Код ошибки: ' . $uploadedFile->getError());
                }

//            } catch (Exception $e) {
//                return $this->respondWithError($e->getCode(), $e->getMessage());
//            }
//        } else {
//            return $this->respondWithError(215);
//        }
    }
}