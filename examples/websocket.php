<?php
require dirname(__DIR__) . '/vendor/autoload.php';

use Yurun\Util\YurunHttp;
use Yurun\Util\HttpRequest;

YurunHttp::setDefaultHandler(\Yurun\Util\YurunHttp\Handler\Swoole::class);
go(function(){
    // 该测试地址随时可能过期
    $url = 'ws://123.207.167.163:9010/ajaxchattest';
    $http = new HttpRequest;
    $http->header('Origin', 'http://coolaf.com');
    $client = $http->websocket($url);
    if(!$client->isConnected())
    {
        throw new \RuntimeException('Connect failed');
    }
    $time = time() . '';
    var_dump('time:', $time);
    $client->send($time);
    $recv = $client->recv();
    var_dump('recv:', $recv);
    $client->close();
});
