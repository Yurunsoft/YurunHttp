<?php
namespace Yurun\Util\YurunHttp\Handler;

use Yurun\Util\YurunHttp\Http\Psr7\Response;

interface IHandler
{
    /**
     * 发送请求
     * @param \Yurun\Util\YurunHttp\Http\Request $request
     * @return void
     */
    public function send($request);

    /**
     * 接收请求
     * @return \Yurun\Util\YurunHttp\Http\Response
     */
    public function recv();
}