<?php
namespace Yurun\Util;

use Yurun\Util\YurunHttp\Handler\Curl;

abstract class YurunHttp
{
    /**
     * 默认处理器类
     * @var string
     */
    private static $defaultHandler = Curl::class;

    /**
     * 设置默认处理器类
     * @param string $class
     * @return void
     */
    public static function setDefaultHandler($class)
    {
        static::$defaultHandler = $class;
    }

    /**
     * 获取默认处理器类
     * @return string
     */
    public static function getDefaultHandler()
    {
        return static::$defaultHandler;
    }

    /**
     * 发送请求并获取结果
     * @param \Yurun\Util\YurunHttp\Http\Request $request
     * @return \Yurun\Util\YurunHttp\Http\Response
     */
    public static function send($request, $handlerClass = null)
    {
        if(null === $handlerClass)
        {
            $handlerClass = static::$defaultHandler;
        }
        $handler = new $handlerClass();
        $handler->send($request);
        return $handler->recv();
    }

}