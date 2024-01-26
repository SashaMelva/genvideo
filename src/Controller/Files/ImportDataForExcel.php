<?php

namespace App\Controller\Files;

use App\Controller\UserController;
use App\Helpers\CheckTokenExpiration;
use App\Helpers\UploadFile;
use App\Models\ImportExcel;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;

class ImportDataForExcel extends UserController
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
                $uploadedFile = $uploadedFiles['excel'];
                $fileNameExcel = $data['project_id'] . '_' . date('Y_m_d_H_i_s') . '_' . floor(microtime(true) * 1000);

                if ($uploadedFile->getError() === UPLOAD_ERR_OK) {

                    $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);

                    if ($extension != 'xlsx') {
                        return $this->respondWithError(400, 'Поддерживаются файлы для импорта только с форматом xlsx');
                    }

                    $filename = UploadFile::action(DIRECTORY_EXCEL_IMPORT, $uploadedFile, $fileNameExcel);

                    if (empty($filename)) {
                        return $this->respondWithError(400, 'Ошибка загрузки файла');
                    }

                    $data['type'] = 2;
                    $file = ImportExcel::addFile($filename, 1, $token->user_id, $data['type'] ?? 1);
                    return $this->respondWithData(['file_name' => $file->file_name, 'id' => $file->id]);

                } else {
                    return $this->respondWithError(400, 'Ошибка получения файла. Код ошибки: ' . $uploadedFile->getError());
                }

            } catch (Exception $e) {
                return $this->respondWithError($e->getCode(), $e->getMessage());
            }
        } else {
            return $this->respondWithError(215);
        }
    }
}