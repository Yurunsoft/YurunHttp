<?php

use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;
use Workerman\Worker;

require_once dirname(dirname(dirname(__DIR__))) . '/vendor/autoload.php';
require_once __DIR__ . '/WorkermanHttp.php';

// #### http worker ####
$http_worker = new Worker('tcp://0.0.0.0:8899');

$http_worker->protocol = WorkermanHttp::class;

// 4 processes
$http_worker->count = 4;

// Emitted when data received
$http_worker->onMessage = function (TcpConnection $connection, Request $request) {
    // var_dump($request->method(), $request->get('a'));
    switch ($request->get('a'))
    {
        case 'info':
            $files = $request->file();
            foreach ($files as &$file)
            {
                $file['hash'] = md5(file_get_contents($file['tmp_name']));
            }
            $connection->send(new Response(200, [
                'Content-Type' => 'application/json',
                'Yurun-Http'   => 'one suo',
            ], json_encode([
                'get'       => $request->get(),
                'post'      => $request->post(),
                'cookie'    => $request->cookie(),
                'header'    => $request->header(),
                'files'     => $files,
                'remote'    => $connection->getRemoteAddress(),
                'method'    => $request->method(),
            ])));
            break;
        case 'setCookie':
            $response = new Response(200, [
                'Content-Type' => 'application/json',
                'Yurun-Http'   => 'one suo',
            ]);
            $response->cookie('a', '1');
            $response->cookie('b', '2', 0);
            $response->cookie('c', '3', 3600, '/');
            $response->cookie('d', '4', null, '/a');
            $response->cookie('e', '5', null, '/', 'localhost');
            $response->cookie('f', '6', null, '/', '', true);
            $response->cookie('g', '7', null, '/', '', true, true);
            $connection->send($response);
            break;
        case 'redirect301':
            $connection->send(new Response(301, [
                'Location' => '/?a=info',
            ]));
            break;
        case 'redirect302':
            $connection->send(new Response(302, [
                'Location' => '/?a=info',
            ]));
            break;
        case 'redirect307':
            $connection->send(new Response(307, [
                'Location' => '/?a=info',
            ]));
            break;
        case 'redirect308':
            $connection->send(new Response(308, [
                'Location' => '/?a=info',
            ]));
            break;
        case 'redirectOther':
            $connection->send(new Response(302, [
                'Location' => 'https://www.httpbin.org/get?id=1',
            ]));
            break;
        case 'redirect':
            $connection->send(new Response(302, [
                'Location' => $request->get('url', '/'),
            ], 'test'));
            break;
        case 'redirectCookie':
            $response = new Response(301, [
                'Location' => '/?a=info',
            ]);
            $response->cookie('redirectCookie', '1');
            $connection->send($response);
            break;
        case 'download1':
            if ('nb' === $request->post('yurunhttp') && 'POST' === $request->method())
            {
                $response = new Response(200, [
                    'Content-Type' => 'text/html; charset=UTF-8',
                ], 'YurunHttp Hello World');
                $response->cookie('a', '1');
                $connection->send($response);
            }
            break;
        case 'download2':
            if ('nb' === $request->post('yurunhttp') && 'POST' === $request->method())
            {
                $connection->send('<h1>YurunHttp Hello World</h1>');
            }
            break;
        case 'download3':
            $response = new Response(200, [
                'Content-Type' => 'text/html; charset=UTF-8',
            ], 'download3');
            $connection->send($response);
            break;
        case 'body':
            $connection->send($request->rawBody());
            break;
        case '304':
            $response = new Response(304);
            $connection->send($response);
            break;
        case 'auth':
            $connection->send($request->header('Authorization', ''));
            break;
        case 'sleep':
            sleep($request->get('time', 0));
            $connection->send('sleep');
            break;
        case 'empty':
            $response = new Response(200, [
                'Vary' => 'Accept-Encoding',
            ]);
            $connection->send(substr((string) $response, 0, -strlen("Content-Length: 0\r\n\r\n")) . "\r\n\r\n", true);
            break;
        default:
            // 默认
            $connection->send('YurunHttp');
    }
};

// Run all workers
Worker::runAll();
