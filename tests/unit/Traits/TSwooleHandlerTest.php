<?php
namespace Yurun\Util\YurunHttp\Test\Traits;

use Yurun\Util\YurunHttp;
use Swoole\Coroutine;

trait TSwooleHandlerTest
{
    protected function call($callable)
    {
        if(!function_exists('\go'))
        {
            $this->markTestSkipped('Does not installed ext/swoole');
        }
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