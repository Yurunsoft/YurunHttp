<?php
$server = new Swoole\WebSocket\Server('127.0.0.1', 8900);

$userNameStore = [];

$server->on('open', function (Swoole\WebSocket\Server $server, $request) {
    
});

$server->on('message', function (Swoole\WebSocket\Server $server, $frame) use(&$userNameStore){
    $data = json_decode($frame->data, true);
    switch($data['action'])
    {
        case 'login':
            $userNameStore[$frame->fd] = $data['username'];
            $server->push($frame->fd, json_encode(['success'=>true]));
            break;
        case 'send':
            $server->push($frame->fd, $userNameStore[$frame->fd] . ':' . $data['message']);
            break;
    }
});

$server->on('close', function ($ser, $fd) {
    
});

$server->start();