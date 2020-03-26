<?php
namespace Yurun\Util\YurunHttp\Test;

use PHPUnit\Framework\TestCase;
use Yurun\Util\YurunHttp;

abstract class BaseTest extends TestCase
{
    /**
     * 请求主机
     *
     * @var string
     */
    protected $host;

    /**
     * WebSocket 请求主机
     *
     * @var string
     */
    protected $wsHost;

    /**
     * WebSocket SSL 请求主机
     *
     * @var string
     */
    protected $wssHost;

    /**
     * Http2 请求主机
     *
     * @var string
     */
    protected $http2Host;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->host = testEnv('HTTP_SERVER_HOST', 'http://127.0.0.1:8899/');
        $this->wsHost = testEnv('WS_SERVER_HOST', 'ws://127.0.0.1:8900/');
        $this->http2Host = testEnv('HTTP2_SERVER_HOST', 'http://127.0.0.1:8901/');
        $this->wssHost = testEnv('WSS_SERVER_HOST', 'wss://127.0.0.1:8902/');
    }

    protected function call($callable)
    {
        YurunHttp::setDefaultHandler(\Yurun\Util\YurunHttp\Handler\Curl::class);
        $callable();
    }

    /**
     * 断言响应
     *
     * @param \Yurun\Util\YurunHttp\Http\Response $response
     * @return void
     */
    protected function assertResponse($response)
    {
        $this->assertEquals(0, $response->errno(), $response->error());
    }

}