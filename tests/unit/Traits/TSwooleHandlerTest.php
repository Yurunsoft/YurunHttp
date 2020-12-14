<?php
namespace Yurun\Util\YurunHttp\Test\Traits;

use Yurun\Util\YurunHttp;
use Swoole\Coroutine;

trait TSwooleHandlerTest
{
    protected function call($callable)
    {
        if(!extension_loaded('swoole'))
        {
            $this->markTestSkipped('Does not installed ext/swoole');
        }
        \Swoole\Runtime::enableCoroutine(true);
        $throwable = null;
        $end = false;
        go(function() use($callable, &$throwable, &$end){
            try {
                YurunHttp::setDefaultHandler(\Yurun\Util\YurunHttp\Handler\Swoole::class);
                $callable();
            } catch(\Throwable $th) {
                $throwable = $th;
            }
            $end = true;
        });
        while(!$end)
        {
            swoole_event_dispatch();
        }
        \Swoole\Runtime::enableCoroutine(false);
        if($throwable)
        {
            throw $throwable;
        }
        else
        {
            $this->assertEquals(1, 1);
        }
    }

}