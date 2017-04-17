<?php
namespace Yurun\Until;
require '../HttpRequest.class.php';
require '../HttpResponse.class.php';
$h = HttpRequest::newSession();
$h->header('test','666');
// socks4代理
$h->proxy('127.0.0.1',1080,'socks4');
// socks5代理
$h->proxy('127.0.0.1',1080,'socks5');
// http代理
$h->proxy('124.88.67.83',843);
// 取消使用代理
$h->useProxy = false;
$r = $h->get('http://www.baidu.com');
var_dump($r->body,$r->headers,$r->cookies);