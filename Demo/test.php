<?php
require dirname(__DIR__) . '/vendor/autoload.php';

use Yurun\Util\HttpRequest;

$http = HttpRequest::newSession();
$response = $http->get('http://www.baidu.com/');
var_dump($response->body());