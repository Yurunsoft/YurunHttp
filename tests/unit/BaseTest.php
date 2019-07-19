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
    protected $host = 'https://test.yurunsoft.com/';

    protected function call($callable)
    {
        YurunHttp::setDefaultHandler('Yurun\Util\YurunHttp\Handler\Curl');
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