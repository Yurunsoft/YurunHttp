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
    $response = $http->get('https://wiki.swoole.com/');
    echo 'html:', PHP_EOL, $response->body(), PHP_EOL;
    var_dump($response->getStatusCode(), $response->getHeaders());
});