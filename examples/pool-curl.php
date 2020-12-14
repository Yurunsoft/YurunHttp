<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use Yurun\Util\HttpRequest;
use Yurun\Util\YurunHttp\ConnectionPool;
use Yurun\Util\YurunHttp\Handler\Curl\CurlHttpConnectionManager;

function dumpPoolInfo()
{
    foreach(CurlHttpConnectionManager::getInstance()->getConnectionPools() as $pool)
    {
        var_dump($pool->getConfig()->getUrl() . ': Count=' . $pool->getCount() . ', Free=' . $pool->getFree() . ', Used=' . $pool->getUsed());
    }
}

// 启用连接池
ConnectionPool::enable();

// 为这个地址设置限制连接池连接数量3个
// 一定不要有 / 及后续参数等
ConnectionPool::setConfig('https://www.httpbin.org', 3);

dumpPoolInfo();

$http = new HttpRequest;
$response = $http->get('https://www.httpbin.org/get?id=1');
var_dump($response->body());

$http = new HttpRequest;
$response = $http->get('https://www.httpbin.org/get?id=1');
var_dump($response->body());

dumpPoolInfo();
