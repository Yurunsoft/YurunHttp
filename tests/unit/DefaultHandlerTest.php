<?php

namespace Yurun\Util\YurunHttp\Test;

use Swoole\Coroutine;
use Yurun\Util\YurunHttp;
use Yurun\Util\YurunHttp\Test\Traits\TSwooleHandlerTest;

class DefaultHandlerTest extends BaseTest
{
    use TSwooleHandlerTest;

    public function testCurl(): void
    {
        if (\extension_loaded('swoole'))
        {
            $this->assertEquals(-1, Coroutine::getuid());
        }
        YurunHttp::setDefaultHandler(null);
        $this->assertNull(YurunHttp::getDefaultHandler());
        $this->assertInstanceOf(\Yurun\Util\YurunHttp\Handler\Curl::class, YurunHttp::getHandler());
    }

    public function testSwoole(): void
    {
        $this->call(function () {
            $this->assertNotEquals(-1, Coroutine::getuid());
            YurunHttp::setDefaultHandler(null);
            $this->assertNull(YurunHttp::getDefaultHandler());
            $this->assertInstanceOf(\Yurun\Util\YurunHttp\Handler\Swoole::class, YurunHttp::getHandler());
        });
    }

    public function testSetDefaultHandler(): void
    {
        YurunHttp::setDefaultHandler(null);
        $this->assertNull(YurunHttp::getDefaultHandler());
        YurunHttp::setDefaultHandler(\Yurun\Util\YurunHttp\Handler\Curl::class);
        $this->assertInstanceOf(\Yurun\Util\YurunHttp\Handler\Curl::class, YurunHttp::getHandler());

        if (\PHP_VERSION_ID >= 50600)
        {
            YurunHttp::setDefaultHandler(null);
            $this->assertNull(YurunHttp::getDefaultHandler());
            YurunHttp::setDefaultHandler(\Exception::class);
            $this->assertEquals(\Exception::class, YurunHttp::getDefaultHandler());
            $this->expectExceptionMessage(sprintf('Class %s does not implement %s', \Exception::class, \Yurun\Util\YurunHttp\Handler\IHandler::class));
            YurunHttp::getHandler();
        }
    }
}
