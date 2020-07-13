<?php
namespace Yurun\Util\YurunHttp\Test;

use Swoole\Coroutine;
use Yurun\Util\YurunHttp;
use Yurun\Util\YurunHttp\Test\Traits\TSwooleHandlerTest;

class DefaultHandlerTest extends BaseTest
{
    use TSwooleHandlerTest;

    public function testCurl()
    {
        if(function_exists('\go'))
        {
            $this->assertEquals(-1, Coroutine::getuid());
        }
        YurunHttp::setDefaultHandler(null);
        $this->assertNull(YurunHttp::getDefaultHandler());
        $this->assertInstanceOf(\Yurun\Util\YurunHttp\Handler\Curl::class, YurunHttp::getHandler());
    }

    public function testSwoole()
    {
        $this->call(function(){
            $this->assertNotEquals(-1, Coroutine::getuid());
            YurunHttp::setDefaultHandler(null);
            $this->assertNull(YurunHttp::getDefaultHandler());
            $this->assertInstanceOf(\Yurun\Util\YurunHttp\Handler\Swoole::class, YurunHttp::getHandler());
        });
    }

    public function testSetDefaultHandler()
    {
        $this->assertNull(YurunHttp::getDefaultHandler());
        YurunHttp::setDefaultHandler(\Exception::class);
        $this->assertEquals(\Exception::class, YurunHttp::getDefaultHandler());
        $this->assertInstanceOf(\Exception::class, YurunHttp::getHandler());
    }

}
