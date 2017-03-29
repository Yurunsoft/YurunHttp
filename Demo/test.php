<?php
namespace Yurun\Until;
require '../HttpRequest.class.php';
require '../HttpResponse.class.php';
$h = HttpRequest::newSession();
$h->header('test','666');
$r = $h->get('http://www.baidu.com');
var_dump($r->body,$r->headers,$r->cookies);
// ($h->put('http://localhost:2222/test2.php','test'));