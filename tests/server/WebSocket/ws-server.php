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

$server->on('close', function ($ser, $fd) use(&$userNameStore){
    if(isset($userNameStore[$fd]))
    {
        unset($userNameStore[$fd]);
    }
});

$wssServer = $server->addlistener('127.0.0.1', 8902, SWOOLE_SOCK_TCP | SWOOLE_SSL);
$wssServer->set([
    'open_websocket_protocol'   =>  true,
    'ssl_cert_file'             =>  dirname(__DIR__, 2) . '/ssl/server.crt',
    'ssl_key_file'              =>  dirname(__DIR__, 2) . '/ssl/server.key',
]);

$server->start();
