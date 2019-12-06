<?php

use Swoole\Coroutine;

$http = new \Swoole\Http\Server('127.0.0.1', 8901);
$http->set([
    'open_http2_protocol'   => true,
    'worker_num'            => 1,
]);
$http->on('request', function (\Swoole\Http\Request $request, \Swoole\Http\Response $response) {
    switch($request->server['path_info'])
    {
        case '/':
            $response->header('trailer', 'yurun');
            $response->trailer('yurun', 'niubi');
            $data = json_decode($request->rawcontent(), true);
            $response->end(json_encode([
                'date'  =>  $data['date'] ?? time(),
                'fd'    =>  $request->fd,
            ]));
            break;
        case '/get':
            $response->header('trailer', 'yurun');
            $response->trailer('yurun', 'niubi');
            $response->end(json_encode([
                'date'  =>  $request->get['date'] ?? time(),
                'fd'    =>  $request->fd,
            ]));
            break;
        case '/sleep':
            $data = json_decode($request->rawcontent(), true);
            Coroutine::sleep(1);
            $response->end(json_encode([
                'date'  =>  $data['date'] ?? time(),
                'fd'    =>  $request->fd,
            ]));
            break;
        default:
            $response->end('gg');
            break;
    }
});
$http->start();
