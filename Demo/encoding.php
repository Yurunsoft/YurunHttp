<?php
// 获取编码转换后的内容
require dirname(__DIR__) . '/vendor/autoload.php';
$http = Yurun\Until\HttpRequest::newSession();
$response = $http->get('http://www.baidu.com/');
var_dump('utf-8:', $response->body); // 或用$response->body()
var_dump('gb2312:', $response->body('UTF-8', 'gb2312'));