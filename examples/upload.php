<?php
/**
 * 上传文件请求示例
 */
require dirname(__DIR__) . '/vendor/autoload.php';

use Yurun\Util\HttpRequest;
use Yurun\Util\YurunHttp\Http\Psr7\UploadedFile;
use Yurun\Util\YurunHttp\Http\Psr7\Consts\MediaType;
use Yurun\Util\YurunHttp;

$http = new HttpRequest;
// 同时支持POST参数、上传文件
$http->content([
    'id'    =>    123456,
    // 显示的文件名；文件类型，可以为null；文件真实路径
    'file'  => new UploadedFile('1.txt', MediaType::TEXT_PLAIN, __FILE__),
]);
// 下面地址改为你自己的地址
$http->post('http://127.0.0.1:8080/Index/upload');