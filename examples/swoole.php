<?php
/**
 * 使用 Swoole 请求示例
 * Swoole 和 Curl的用法基本相同，都可以用 HttpRequest 和 PSR-7 来构建请求，唯一不同的请看 psr7Ex.php 中的扩展参数。
 */

require dirname(__DIR__) . '/vendor/autoload.php';

use Yurun\Util\YurunHttp;
use Yurun\Util\HttpRequest;

// 设置默认请求处理器为 Swoole
// YurunHttp::setDefaultHandler(\Yurun\Util\YurunHttp\Handler\Swoole::class);
// Swoole 处理器必须在协程中调用
go('test');

function test()
{
    $http = new HttpRequest;
    $response = $http->get('http://127.0.0.1:8901');
    echo 'html:', PHP_EOL, $response->body();
}