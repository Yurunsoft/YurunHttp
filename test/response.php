<?php
/**
 * 响应结果示例
 */
require dirname(__DIR__) . '/vendor/autoload.php';

use Yurun\Util\HttpRequest;

$http = new HttpRequest;
$response = $http->get('http://www.baidu.com');

// 主体
echo 'html:', PHP_EOL, $response->body(), PHP_EOL;
// echo 'html:', PHP_EOL, $response->body('gb2312'); // 编码转换，默认转为utf-8
// echo 'html:', PHP_EOL, $response->body('gb2312', 'utf-8'); // 编码转换，指定转为utf-8

// 主体格式化
// echo 'json:', PHP_EOL, $response->json(); // 对象
// echo 'json:', PHP_EOL, $response->json(true); // 数组
// echo 'xml:', PHP_EOL, $response->xml(); // SimpleXMLElement
// echo 'xml:', PHP_EOL, $response->xml(true); // 数组

// 错误码、错误信息
echo 'errno:', $response->errno(), ', error:', $response->error(), PHP_EOL;
// echo 'errno:', $response->getErrno(), ', error:', $response->getError(); // 上下两种用法一样

// 状态码
echo 'statusCode:', $response->getStatusCode(), PHP_EOL;
// echo 'statusCode:', $response->httpCode(), PHP_EOL; // 上下两种用法一样

// 响应头

// - 所有响应头
echo 'headers:', PHP_EOL;
var_dump($response->getHeaders());
echo PHP_EOL;

// - 单个响应头数组
echo 'header-server-array:', PHP_EOL;
var_dump($response->getHeader('Server'));
echo PHP_EOL;

// - 单个响应头字符串
echo 'header-server-string:', $response->getHeaderLine('Server'), PHP_EOL;

// - 响应头是否存在

echo 'hasHeader:', $response->hasHeader('Server') ? 'true' : 'false', PHP_EOL;

// Cookie

// - 所有cookie键值
echo 'cookie values:', PHP_EOL;
var_dump($response->getCookieParams());
echo PHP_EOL;

// - 取cookie值
echo 'cookie value:', $response->getCookie('BAIDUID'), PHP_EOL;
// echo $response->getCookie('BAIDUID', 'default value');

// - 所有cookie原始值,包含expires、path、domain等
echo 'cookie origin values:', PHP_EOL;
var_dump($response->getCookieOriginParams());
echo PHP_EOL;

// - 取cookie原始值,包含expires、path、domain等
echo 'cookie origin value:', PHP_EOL;
var_dump($response->getCookieOrigin('BAIDUID'));
// var_dump($response->getCookieOrigin('BAIDUID', 'default value'));
echo PHP_EOL;

// 请求耗时
echo 'http request time:', $response->getTotalTime(), 's', PHP_EOL;
