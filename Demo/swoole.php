<?php
require dirname(__DIR__) . '/vendor/autoload.php';

use Yurun\Util\YurunHttp\Http\Request;
use Yurun\Util\YurunHttp;

YurunHttp::setDefaultHandler('Yurun\Util\YurunHttp\Handler\Swoole');
// 必须使用在协程中
go(function(){
	$request = new Request('http://www.baidu.com');
	$response = YurunHttp::send($request);
	var_dump($response->getCookieOrigin('BAIDUID'), $response->getCookie('BAIDUID'), $response->getBody(), $response->getHeaders(), $response->getHeader('Server'), $response->getHeaderLine('Server'), $response->getProtocolVersion(), $response->getReasonPhrase(), $response->getStatusCode(), $response->errno(), $response->error(), $response->totalTime());
});