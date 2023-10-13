<?php

namespace App\Controller\Video;

use App\Controller\UserController;
use App\Helpers\CheckTokenExpiration;
use App\Models\ContentVideo;
use App\Models\ListProject;
use App\Models\Project;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class GetAllContent extends UserController
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function action(): ResponseInterface
    {
        $access_token = $this->request->getHeaderLine('Token');
        $data = json_decode($this->request->getBody()->getContents(), true);
        $projectId = $data['project_id'];

        if (CheckTokenExpiration::action($this->container->get('jwt-secret'), $access_token)) {

        try {
//                $token = JWT::decode($access_token, new Key($this->container->get('jwt-secret'), 'HS256'));
            $page = $data['page'] ?? 1;
            $pageSize = $data['page_size'] ?? 30;
            $countRows = ContentVideo::countContent((int)$projectId);
            $totalPage = ceil($countRows / $pageSize);

            if ($page > $totalPage) {
                return $this->respondWithError(400, 'Данной страницы не существует');
            }

//            $project = Project::fullInfo((int)$projectId);
            $rowStart = ($page - 1) * $pageSize + 1;
            $rowEnd = min($page * $pageSize, $countRows);

            $resultData = [
                'current_page' => $page,
                'page_size' => $pageSize,
                'total_rows' => $countRows,
                'total_page' => $totalPage,
                'row_start' => $rowStart,
                'row_end' => $rowEnd,
                'rows' => [],
//                'project_data' => $project,
            ];


            if ($countRows > 0) {
                $resultData['rows'] = ContentVideo::findByList((int)$projectId, ($page - 1) * $pageSize, $pageSize);

            }

            return $this->respondWithData($resultData);

        } catch (Throwable $e) {
            return $this->respondWithError($e->getCode(), $e->getMessage());
        }
        } else {
            return $this->respondWithError(215);
        }
    }
}