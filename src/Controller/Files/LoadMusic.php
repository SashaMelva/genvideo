<?php

namespace App\Controller\Files;

use App\Controller\UserController;
use App\Helpers\CheckTokenExpiration;
use App\Models\AdditionalVideo;
use App\Models\MusicVideo;
use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;

class LoadMusic  extends UserController
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function action(): ResponseInterface
    {
        $access_token = $this->request->getHeaderLine('token');
        $musicId = $this->request->getAttribute('id');
        $sound = MusicVideo::findByID($musicId);

        if (CheckTokenExpiration::action($this->container->get('jwt-secret'), $access_token)) {

            try {
                $file = DIRECTORY_MUSIC . $sound['file_name'];
                if (file_exists($file)) {

                    if (ob_get_level()) {
                        ob_end_clean();
                    }

                    header('Access-Control-Allow-Origin: *');
                    header('Content-Description: File Transfer');
                    header('Content-Type: application/octet-stream');
                    header('Content-Disposition: attachment; filename=' . basename($file));
                    header('Content-Transfer-Encoding: binary');
                    header('Expires: 0');
                    header('Cache-Control: must-revalidate');
                    header('Pragma: public');
                    header('Content-Length: ' . filesize($file));

                    if ($fd = fopen($file, 'rb')) {
                        while (!feof($fd)) {
                            print fread($fd, 1024);
                        }
                        fclose($fd);
                    }

                    return $this->respondWithData([
                        'status' => 'success',
                        'message' => 'Файл успешно отдан'
                    ]);
                } else {
                    return $this->respondWithData([
                        'status' => 'error',
                        'message' => 'Файл не найден'
                    ]);
                }

            } catch (Exception $e) {
                return $this->respondWithError($e->getCode(), $e->getMessage());
            }
        } else {
            return $this->respondWithError(215);
        }
    }
}