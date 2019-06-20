<?php
namespace Yurun\Util\YurunHttp\Test;

use PHPUnit\Framework\TestCase;
use Yurun\Util\YurunHttp;
use Yurun\Util\YurunHttp\Handler\Curl;

abstract class BaseTest extends TestCase
{
    /**
     * 请求主机
     *
     * @var string
     */
    protected $host = 'http://127.0.0.1:8899/';

    protected function call($callable)
    {
        YurunHttp::setDefaultHandler(Curl::class);
        $callable();
    }

}