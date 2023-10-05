<?php

namespace App\Controller\Video;

use App\Controller\UserController;
use App\Helpers\CheckTokenExpiration;
use App\Models\ContentVideo;
use App\Models\ListAdditionalVideo;
use App\Models\ListImage;
use App\Models\ListMusic;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;

class DownloadVideo extends UserController
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function action(): ResponseInterface
    {
        $access_token = $this->request->getHeaderLine('token');
        $token = JWT::decode($access_token, new Key($this->container->get('jwt-secret'), 'HS256'));
        $data = json_decode($this->request->getBody()->getContents(), true);

        if (CheckTokenExpiration::action($this->container->get('jwt-secret'), $access_token)) {

            try {
                $file = DIRECTORY_VIDEO . $data['file_name'];
                if (file_exists($file)) {
                    // сбрасываем буфер вывода PHP, чтобы избежать переполнения памяти выделенной под скрипт
                    // если этого не сделать файл будет читаться в память полностью!
                    if (ob_get_level()) {
                        ob_end_clean();
                    }
                    // заставляем браузер показать окно сохранения файла
                    header('Content-Description: File Transfer');
                    header('Content-Type: application/octet-stream');
                    header('Content-Disposition: attachment; filename=' . basename($file));
                    header('Content-Transfer-Encoding: binary');
                    header('Expires: 0');
                    header('Cache-Control: must-revalidate');
                    header('Pragma: public');
                    header('Content-Length: ' . filesize($file));
                    // читаем файл и отправляем его пользователю

                    return $this->respondWithData(readfile($file));
                }

                return $this->respondWithData(400, 'Неудалось скачать файл');
            } catch (Exception $e) {
                return $this->respondWithError($e->getCode(), $e->getMessage());
            }
        } else {
            return $this->respondWithError(215);
        }
    }
}