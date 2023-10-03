<?php

use Psr\Http\Message\RequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\App;

return function(App $app)
{
    $app->add(function (RequestInterface $request, RequestHandlerInterface $handler) {

        $response = $handler->handle($request);
//        return $response
//            ->withHeader('Access-Control-Allow-Origin', '*')
//            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
//            ->withHeader('Access-Control-Allow-Headers', 'Origin, Content-Type, Accept, Authorization, X-Features, Token')
//            ->withHeader('Expires', 'Mon, 26 Jul 1997 05:00:00 GMT')
//            ->withHeader('Cache-Control', 'no-store, no-cache, must-revalidate')
//            ->withHeader('Cache-Control', 'post-check=0, pre-check=0')
//            ->withHeader('Pragma', 'no-cache')->withStatus(200);


//        if ($request->getAttribute('ip_address') == '46.197.179.16') {
//            $host = 'http://redwtest.localhost.ru:3000';
//        }
//
//        if (str_contains($request->getHeader('Origin')[0], 'https://courseup.ru')) {
//            $host = 'https://courseup.ru';
//        } elseif (str_contains($request->getHeader('Origin')[0], '.courseup.ru') ||
//            str_contains($request->getHeader('Origin')[0], '.localhost.ru:3000')) {
//            $host = $request->getHeader('Origin')[0];
//        }
        //withCredentials
        if ($request->getMethod() == 'OPTIONS') {
            return $response
                ->withHeader('Access-Control-Allow-Methods', 'GET, POST, DELETE, OPTIONS')
                ->withHeader('Access-Control-Allow-Origin', '*')
               // ->withHeader('Access-Control-Allow-Credentials', 'true')
                ->withHeader('Access-Control-Allow-Headers', 'Origin, Content-Type, Accept, Authorization, X-Features, Token')
                ->withHeader('Content-Type', 'text/plain charset=UTF-8')
                ->withHeader('Content-Length', 0)->withStatus(204);
        } else {
            return $response
                ->withHeader('Access-Control-Allow-Methods', 'GET, POST, DELETE, OPTIONS')
                ->withHeader('Access-Control-Allow-Origin', '*')
              //  ->withHeader('Access-Control-Allow-Credentials', 'true')
                ->withHeader('Access-Control-Allow-Headers', 'Origin, Content-Type, Accept, Authorization, X-Features, Token');
        }
    });

};
