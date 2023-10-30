<?php

namespace App\Controller\Files;

use App\Controller\UserController;
use App\Helpers\CheckTokenExpiration;
use App\Helpers\UploadFile;
use App\Models\MusicVideo;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;

class UploadMusic extends UserController
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
                $uploadedFile = $uploadedFiles['music'];
                $fileNameMusic = $data['project_id'] . '-' . date('Y_m_d_H_i_s');

                if ($uploadedFile->getError() === UPLOAD_ERR_OK) {

                    if ($data['type_music'] == 'sound') {
                        $fileName = UploadFile::action(DIRECTORY_MUSIC, $uploadedFile, $fileNameMusic);
                        $filePath = RELATIVE_PATH_MUSIC . $fileName;
                    }

                    if (empty($fileName) || empty($filePath)) {
                        return $this->respondWithError(400, 'Ошибка загрузки музыки');
                    }

                    $music = MusicVideo::addMusic($fileName, $filePath, '00:00', $data['project_id'],$data['name'] ?? '', $data['type_music']);
                    return $this->respondWithData(['path' => $music->file_path, 'id' => $music->id]);

                } else {
                    return $this->respondWithError(400, 'Ошибка получения музыки. Код ошибки: ' . $uploadedFile->getError());
                }

            } catch (Exception $e) {
                return $this->respondWithError($e->getCode(), $e->getMessage());
            }
        } else {
            return $this->respondWithError(215);
        }
    }
}