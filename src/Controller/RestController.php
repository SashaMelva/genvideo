<?php

namespace App\Controller;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpNotFoundException;
use Slim\Psr7\Stream;

abstract class RestController
{
    protected ContainerInterface $container;

    protected ServerRequestInterface $request;

    protected ResponseInterface $response;

    protected array $args;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->request = $request;
        $this->response = $response;
        $this->args = $args;
        try {
            return $this->action();
        } catch (\Exception $e) {
            throw new HttpNotFoundException($this->request, $e->getMessage());
        }
    }

    abstract protected function action(): ResponseInterface;

    protected function getFormData(): object|array|null
    {
        return $this->request->getParsedBody();
    }

    protected function respondWithData($data = null, int $statusCode = 200, $statusCookie = false): ResponseInterface
    {
        $this->response->getBody()->write(json_encode($data));

        $newResponse = $this->response->withHeader('Content-Type', 'application/json');

        if ($statusCookie) {
            $newResponse = $this->response->withHeader('Set-Cookie', $statusCookie);
        }

        return $newResponse->withStatus($statusCode);
    }

    protected function respondWithFile($filePath, $outputName = null, int $statusCode = 200): ResponseInterface
    {

        $size = filesize($filePath);
        $path_parts = pathinfo($filePath);
        $ext = strtolower($path_parts["extension"]);

        if(!$outputName) {
            $outputName = $path_parts["basename"];
        } else {
            if(count(explode('.', $outputName)) <= 1){
                $outputName = $outputName.'.'.$ext;
            }
        }

        return $this->response
            ->withHeader('Content-Transfer-Encoding', 'UTF-8')
            ->withHeader('Content-Type', 'application/csv;charset=UTF-8')
            ->withHeader("Content-Disposition",'attachment; filename='.'"'.$outputName.'"')
            ->withHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
            ->withHeader('Pragma', 'public')
            ->withHeader('expires', 0)
            ->withHeader("Content-length", $size)
            ->withBody((new Stream(fopen($filePath, 'r'))))
            ->withStatus($statusCode);
    }

    protected function respondWithError(int $errorCode, mixed $errorMessage = null): ResponseInterface
    {
        return match ($errorCode) {
            23505 => $this->respondWithData(['code' => 23505, 'message' => 'Resource already exist'], 400),
            253 => $this->respondWithData(['code' => 253, 'message' => $errorMessage], 400),
            221 => $this->respondWithData(['code' => 221, 'message' => 'Permission denied'], 400),
            215 => $this->respondWithData(['code' => 215, 'message' => 'Истек срок действия токена'], 401),
            219 => $this->respondWithData(['code' => 219, 'message' => 'refresh token not found'], 400),
            217 => $this->respondWithData(['code' => 217, 'message' => 'refresh token expired'], 401),
            222 => $this->respondWithData(['code' => 222, 'message' => $errorMessage], 400),
            0, 404 => $this->respondWithData(['code' => 404, 'message' => 'Страница не найдена'], 404),
            default => $this->respondWithData($errorMessage, 400),
        };
    }
}