<?php
namespace Yurun\Util\YurunHttp\Test\Traits;

use Yurun\Util\YurunHttp;

trait TSwooleHandlerTest
{
    protected function call($callable)
    {
        if(!function_exists('\go'))
        {
            $this->markTestSkipped('Does not installed ext/swoole');
        }
        YurunHttp::setDefaultHandler('Yurun\Util\YurunHttp\Handler\Swoole');
        $callable();
    }
}