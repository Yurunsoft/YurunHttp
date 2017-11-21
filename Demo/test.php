<?php
// 很杂的demo
namespace Yurun\Until;
require dirname(__DIR__) . '/vendor/autoload.php';
$h = HttpRequest::newSession();
// 设置单个header
$h->header('test','666');
// 设置多个header
$h->headers(array(
	'test2'	=>	'777',
	'test3'	=>	'888',
));
// socks4代理
$h->proxy('127.0.0.1',1080,'socks4');
// socks5代理
$h->proxy('127.0.0.1',1080,'socks5');
// http代理
$h->proxy('124.88.67.83',843);
// 取消使用代理
$h->useProxy = false;
// 设置POST内容方法1
$h->content('123');
// 设置POST内容方法2
$h->params(array(
	'id'	=>	2,
));
// cURL设置多个
$h->options(array(
	CURLOPT_HEADER	=>	true,
));
// cURL设置单个
$h->option(CURLOPT_HEADER,true);
// POST请求+POST参数
$r = $h->post('https://www.baidu.com/s',array('wd'=>'搜索词'));
// GET请求+GET参数+失败重试3次
$r = $h->retry(3)->get('https://www.baidu.com/s',array('wd'=>'搜索词'));
var_dump($r->body,$r->headers,$r->cookies);