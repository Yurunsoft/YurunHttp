<?php

use Workerman\Connection\TcpConnection;
use Workerman\Worker;

require_once dirname(dirname(dirname(__DIR__))) . '/vendor/autoload.php';

$userNameStore = [];

// WS (WebSocket) Server
$ws_worker = new Worker('websocket://0.0.0.0:8900');
$ws_worker->count = 4;
$ws_worker->name = 'YurunHttp WS Test';

$ws_worker->onMessage = function (TcpConnection $connection, $data) use (&$userNameStore) {
    $data = json_decode($data, true);
    if (!is_array($data))
    {
        return;
    }
    switch ($data['action'] ?? null)
    {
        case 'login':
            $userNameStore[$connection->id] = $data['username'];
            $connection->send(json_encode(['success' => true]));
            break;
        case 'send':
            if (isset($userNameStore[$connection->id]))
            {
                $connection->send($userNameStore[$connection->id] . ':' . $data['message']);
            }
            break;
    }
};

$ws_worker->onClose = function (TcpConnection $connection) use (&$userNameStore) {
    if (isset($userNameStore[$connection->id]))
    {
        unset($userNameStore[$connection->id]);
    }
};

// Run all workers
Worker::runAll();
