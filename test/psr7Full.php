<?php
/**
 * 使用 PSR-7 标准构建请求完整示例
 */
require dirname(__DIR__) . '/vendor/autoload.php';

use Yurun\Util\YurunHttp\Http\Request;
use Yurun\Util\YurunHttp;
use Yurun\Util\YurunHttp\Stream\MemoryStream;
use Yurun\Util\YurunHttp\Http\Psr7\UploadedFile;
use Yurun\Util\YurunHttp\Http\Psr7\Uri;

test();

function test()
{
    $url = 'http://www.baidu.com';

    // 构造方法定义：__construct($uri = null, array $headers = [], $body = '', $method = RequestMethod::GET, $version = '1.1', array $server = [], array $cookies = [], array $files = [])
    $request = new Request($url);

    // uri
    // $request = $request->withUri(new Uri($url));

    // header
    $request = $request->withHeader('User-Agent', '1')
                    ->withAddedHeader('User-Agent', '2');
    // ↑ 最终的 User-Agent:1,2，两种用法自己体会
    // $request->withoutHeader('User-Agent'); // 移除 User-Agent 请求头

    // cookie
    $request = $request->withCookieParams([
        'k1'    =>    'v1',
        'k2'    =>    'v2',
    ]);

    // body/post参数
    $request = $request->withBody(new MemoryStream('id=1&name=2'));
    // $request = $request->withBody(new MemoryStream(http_build_query([
    //     'id'    =>    1,
    //     'name'    =>    2,
    // ])));

    // GET参数
    $request = $request->withQueryParams([
        'get1'    =>    '111',
        'get2'    =>    '222',
    ]);

    // 请求方法
    $request = $request->withMethod('POST');

    // 上传文件
    // $request = $request->withUploadedFiles([
    //     new UploadedFile('1.txt', MediaType::TEXT_PLAIN, __FILE__),
    //     new UploadedFile('2.txt', MediaType::TEXT_PLAIN, __FILE__),
    // ]);

    // 发送请求并获取结果
    $response = YurunHttp::send($request);

    var_dump($response);
}