<?php

namespace Yurun\Util\YurunHttp\Test\Traits;

use Yurun\Util\YurunHttp;

trait TSwooleHandlerTest
{
    /**
     * @param callable $callable
     *
     * @return void
     */
    protected function call($callable)
    {
        if (!\extension_loaded('swoole'))
        {
            $this->markTestSkipped('Does not installed ext/swoole');
        }
        if (\defined('SWOOLE_HOOK_ALL'))
        {
            $flags = \SWOOLE_HOOK_ALL;
            if (\defined('SWOOLE_HOOK_NATIVE_CURL'))
            {
                $flags ^= \SWOOLE_HOOK_NATIVE_CURL;
            }
        }
        else
        {
            $flags = true;
        }
        \Swoole\Runtime::enableCoroutine($flags);
        $throwable = null;
        $end = false;
        go(function () use ($callable, &$throwable, &$end) {
            try
            {
                YurunHttp::setDefaultHandler(\Yurun\Util\YurunHttp\Handler\Swoole::class);
                $callable();
            }
            catch (\Throwable $th)
            {
                $throwable = $th;
            }
            $end = true;
        });
        // @phpstan-ignore-next-line
        while (!$end)
        {
            swoole_event_dispatch();
        }
        // @phpstan-ignore-next-line
        \Swoole\Runtime::enableCoroutine(false);
        if ($throwable)
        {
            throw $throwable;
        }
        else
        {
            $this->assertEquals(1, 1);
        }
    }
}
