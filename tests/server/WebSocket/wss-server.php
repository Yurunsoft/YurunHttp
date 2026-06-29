<?php

$userNameStore = [];

$server = new Swoole\WebSocket\Server('127.0.0.1', 8902, \SWOOLE_PROCESS, \SWOOLE_SOCK_TCP | \SWOOLE_SSL);

$server->set([
    'open_websocket_protocol'   => true,
    'worker_num'                => 1,
    'ssl_cert_file'             => dirname(dirname(__DIR__)) . '/ssl/server.crt',
    'ssl_key_file'              => dirname(dirname(__DIR__)) . '/ssl/server.key',
]);

$server->on('open', function (Swoole\WebSocket\Server $server, $request) {
});

$server->on('message', function (Swoole\WebSocket\Server $server, $frame) use (&$userNameStore) {
    $data = json_decode($frame->data, true);
    switch ($data['action'])
    {
        case 'login':
            $userNameStore[$frame->fd] = $data['username'];
            $server->push($frame->fd, json_encode(['success' => true]));
            break;
        case 'send':
            $server->push($frame->fd, $userNameStore[$frame->fd] . ':' . $data['message']);
            break;
    }
});

$server->on('close', function ($ser, $fd) use (&$userNameStore) {
    if (isset($userNameStore[$fd]))
    {
        unset($userNameStore[$fd]);
    }
});

$server->start();
