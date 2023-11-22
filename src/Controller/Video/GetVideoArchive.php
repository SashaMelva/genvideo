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
        $videoIdArray = explode(',',$videoIds);
//        $access_token = $this->request->getAttribute('token');

        var_dump($videoIdArray);
//        if (CheckTokenExpiration::action($this->container->get('jwt-secret'), $access_token)) {

            try {
                $contents = [];
                $zipName = 'archive_' . date('Y_m_d_H_i_s') . '_' . floor(microtime(true) * 1000) . '.zip';
                $zipCommand = 'zip ' . DIRECTORY_ARCHIVE . $zipName . ' ';


                if ($type == 'content') {
                    foreach ($videoIdArray as $id) {
                        $contents[] = ContentVideo::findByID($id);
                    }

                    foreach ($contents as $content) {
                        $zipCommand .= ' ' . DIRECTORY_VIDEO . $content['file_name'];

                        if (!is_null($content['preview_file_name'])) {
                            $zipCommand .= ' ' . DIRECTORY_PREVIEW . $content['preview_file_name'];
                        }
                    }
                }

                if ($type == 'video') {
                    foreach ($videoIdArray as $id) {
                        $contents[] = AdditionalVideo::findByID($id);
                    }

                    foreach ($contents as $content) {
                        $zipCommand .= ' ' . DIRECTORY_ADDITIONAL_VIDEO . $content['file_path'];
                    }
                }

                var_dump($zipCommand);
                shell_exec($zipCommand);

                $file = DIRECTORY_ARCHIVE . $zipName;
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
//        } else {
//            return $this->respondWithError(215);
//        }
    }
}