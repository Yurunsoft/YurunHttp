<?php

namespace Yurun\Util\YurunHttp\Test\Http3;

use Yurun\Util\HttpRequest;
use Yurun\Util\YurunHttp;
use Yurun\Util\YurunHttp\Handler\Curl;
use Yurun\Util\YurunHttp\Test\BaseTest;

class Http3Test extends BaseTest
{
    public function testHttp3(): void
    {
        // HTTP/3 仅 Curl Handler 支持
        if (!\defined('\CURL_HTTP_VERSION_3'))
        {
            $this->markTestSkipped('CURL_HTTP_VERSION_3 未定义，当前 PHP/libcurl 版本不支持 HTTP/3');
        }

        $curlVersion = \curl_version();
        // HTTP/3 需要 libcurl >= 7.66.0（0x074200）
        if ($curlVersion['version_number'] < 0x074200)
        {
            $this->markTestSkipped(sprintf('libcurl %s < 7.66.0，不支持 HTTP/3', $curlVersion['version']));
        }

        // 检测 libcurl 是否编译了 HTTP/3(QUIC) 支持
        if (\defined('\CURL_VERSION_HTTP3') && !($curlVersion['features'] & \CURL_VERSION_HTTP3))
        {
            $this->markTestSkipped('当前 libcurl 未编译 HTTP/3(QUIC) 支持');
        }

        // 强制使用 Curl Handler（Swoole 不支持 HTTP/3）
        YurunHttp::setDefaultHandler(Curl::class);

        $http = new HttpRequest();
        $http->protocolVersion = '3.0';
        $http->timeout = 10000;
        $http->connectTimeout = 10000;

        $response = $http->get('https://www.taobao.com/');

        // 环境/网络不支持时跳过，而不是判定失败
        if (0 !== $response->errno())
        {
            $this->markTestSkipped(sprintf('HTTP/3 请求失败（环境/网络原因）: %s', $response->error()));
        }

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotEmpty($response->getBody());

        // 校验响应确实是 HTTP/3（QUIC）
        $this->assertEquals('3.0', $response->getHttpVersion(), sprintf(
            '响应协议版本不是 HTTP/3，实际为: %s',
            $response->getHttpVersion()
        ));
    }
}
