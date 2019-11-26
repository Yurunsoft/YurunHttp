<?php
$http = new \Swoole\Http\Server('127.0.0.1', 8901);
$http->set([
    'open_http2_protocol'   => true,
    'worker_num'            => 1,
]);
$http->on('request', function (\Swoole\Http\Request $request, \Swoole\Http\Response $response) {
    $response->header('trailer', 'yurun');
    $response->trailer('yurun', 'niubi');
    $data = json_decode($request->rawcontent(), true);
    $response->end(json_encode([
        'date'  =>  $data['date'] ?? time(),
        'fd'    =>  $request->fd,
    ]));
});
$http->start();
