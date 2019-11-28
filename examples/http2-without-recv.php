<?php
/**
 * 简单用法示例
 */
require dirname(__DIR__) . '/vendor/autoload.php';

use Yurun\Util\HttpRequest;
use Yurun\Util\YurunHttp;
use Yurun\Util\YurunHttp\Handler\Swoole;

YurunHttp::setDefaultHandler(Swoole::class);

go(function(){
    $http = new HttpRequest;
    $http->protocolVersion = '2.0';
    $http->sendHttp2WithoutRecv('https://wiki.swoole.com/', null, 'GET');
});