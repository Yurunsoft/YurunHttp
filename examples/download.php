<?php
/**
 * 下载文件请求示例
 */
require dirname(__DIR__) . '/vendor/autoload.php';

use Yurun\Util\HttpRequest;
use Yurun\Util\YurunHttp;
use Yurun\Util\YurunHttp\Handler\Swoole;

$url = 'http://www.baidu.com';

$http = new HttpRequest;

$http->download(__DIR__ . '/save.*', $url); // 如果文件名设为save.*，.* 则代表自动识别扩展名

// 也支持 POST 下载：
// $body = '';
// $http->download(__DIR__ . '/save.html', $url, $body, 'POST');