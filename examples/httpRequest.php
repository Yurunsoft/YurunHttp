<?php
/**
 * 使用HttpRequest类请求示例
 */
require dirname(__DIR__) . '/vendor/autoload.php';

use Yurun\Util\HttpRequest;
use Yurun\Util\YurunHttp\Http\Psr7\UploadedFile;
use Yurun\Util\YurunHttp\Http\Psr7\Consts\MediaType;

$url = 'http://127.0.0.1:8080/Index/upload';

$http = new HttpRequest;

// 设置 header
$http->header('aa', '1');

// 批量设置 header
$http->headers([
    'bb'    =>    '2',
    'cc'    =>    '3',
]);

// 设置 cookie
$http->cookie('c1', 'abc1');

// 批量设置 cookie
$http->cookies([
    'c2'    =>    'abc2',
    'c3'    =>    'abc3',
]);

// 限速，单位字节，为0不限制
// $http->limitRate(0);

// // http代理
// $http->proxy('127.0.0.1', 8080);
// // 代理验证
// $http->proxyAuth('username', 'password');

// // socks代理
// $http->proxy('127.0.0.1', 8080, 'socks5');
// // 代理验证
// $http->proxyAuth('username', 'password');

// 失败重试
// $http->retry(3);

// 保存文件路径
// $http->saveFile('文件保存路径');

// 证书、私钥
// $http->sslCert('证书路径');
// $http->sslKey('私钥路径');

// 超时时间，单位：毫秒
// $http->timeout(10000);

// http认证
// $http->userPwd('username', 'password');

// body

// 原样提交
// $http->content('id=1&name=2');

// get参数编码
// $http->content([
//     'id'    =>    2,
//     'name'    =>    3,
// ]);

// 带参数+文件上传
// $http->content([
//     'id'    =>    3,
//     'name'    =>    4,
//     new UploadedFile('1.txt', MediaType::TEXT_PLAIN, __FILE__),
// ]);

// 也可以如下方式调用，$body可以是上面传入content()方法中的值
// $response = $http->post($url, $body);

$response = $http->post($url);

// 以上方法都可以连写比如：
// $http->ua('userAgent')->timeout(10000)->get($url);