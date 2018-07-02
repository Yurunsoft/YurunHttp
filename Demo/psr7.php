<?php
require dirname(__DIR__) . '/vendor/autoload.php';

use Yurun\Util\YurunHttp\Http\Request;
use Yurun\Util\YurunHttp;

$request = new Request('http://www.baidu.com');
$response = YurunHttp::send($request);
var_dump($response->getCookieOrigin('BAIDUID'), $response->getCookie('BAIDUID'), $response->getBody(), $response->getHeaders(), $response->getHeader('Server'), $response->getHeaderLine('Server'), $response->getProtocolVersion(), $response->getReasonPhrase(), $response->getStatusCode(), $response->errno(), $response->error(), $response->totalTime());