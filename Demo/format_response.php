<?php
// 获取格式化后的数据
require dirname(__DIR__) . '/vendor/autoload.php';

$assoc = true; //  为true时返回数组，为false时返回对象

$http = Yurun\Until\HttpRequest::newSession();
var_dump('jsonp:', $http->get('https://graph.qq.com/oauth2.0/token')->jsonp($assoc));

$http = Yurun\Until\HttpRequest::newSession();
var_dump('json:', $http->get('https://api.weibo.com/oauth2/access_token')->json($assoc));

$http = Yurun\Until\HttpRequest::newSession();
var_dump('xml:', $http->get('http://wthrcdn.etouch.cn/WeatherApi?citykey=')->xml($assoc));