<?php

namespace App\Controller\Video;

use App\Controller\UserController;
use App\Helpers\CheckTokenExpiration;
use App\Models\AdditionalVideo;
use App\Models\ContentVideo;
use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use ZipArchive;

class GetVideoArchive extends UserController
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function action(): ResponseInterface
    {
        $type = $this->request->getAttribute('type');
        $videoIds = $this->request->getAttribute('id');
        $videoIdArray = explode(',', $videoIds);
        $access_token = $this->request->getAttribute('token');

        if (CheckTokenExpiration::action($this->container->get('jwt-secret'), $access_token)) {

            try {
                $contents = [];

                $zip = new ZipArchive();
                $zipFile = DIRECTORY_ARCHIVE . 'archive_' . date('Y_m_d_H_i_s') . '_' . floor(microtime(true) * 1000) . '.zip';
                $zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

                if ($type == 'content') {
                    foreach ($videoIdArray as $id) {
                        $contents[] = ContentVideo::findByID($id);
                    }

                    foreach ($contents as $content) {
                        $nameFile = str_replace('.', '', $content['content_name']);
                        $zip->addFile(DIRECTORY_VIDEO . $content['file_name'], 'видео' . DIRECTORY_SEPARATOR . $nameFile . '.mp4');

                        if (!is_null($content['preview_file_name'])) {
                            $zip->addFile(DIRECTORY_PREVIEW . $content['preview_file_name'], 'превью' . DIRECTORY_SEPARATOR . $nameFile . '.jpg');
                        }
                    }
                }

                if ($type == 'video') {
                    foreach ($videoIdArray as $id) {
                        $contents[] = AdditionalVideo::findByID($id);
                    }

                    foreach ($contents as $content) {
                        $zip->addFile(DIRECTORY_ADDITIONAL_VIDEO . $content['file_name'], 'видео' . DIRECTORY_SEPARATOR . $content['name'] . '.mp4');
                    }
                }
                $zip->close();

                if (file_exists($zipFile)) {

                    if (ob_get_level()) {
                        ob_end_clean();
                    }

                    header('Access-Control-Allow-Origin: *');
                    header('Content-Description: File Transfer');
                    header('Content-Type: application/octet-stream');
                    header('Content-Disposition: attachment; filename=' . basename($zipFile));
                    header('Content-Transfer-Encoding: binary');
                    header('Expires: 0');
                    header('Cache-Control: must-revalidate');
                    header('Pragma: public');
                    header('Content-Length: ' . filesize($zipFile));

                    if ($fd = fopen($zipFile, 'rb')) {
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