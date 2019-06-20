<?php
/**
 * 简单用法示例
 */
require dirname(__DIR__) . '/vendor/autoload.php';

use Yurun\Util\HttpRequest;

$http = new HttpRequest;
$response = $http->get('http://www.baidu.com');
echo 'html:', PHP_EOL, $response->body();
