<?php
namespace Yurun\Util\YurunHttp\Handler;

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

    /**
     * Get cookie 管理器
     *
     * @return  \Yurun\Util\YurunHttp\Cookie\CookieManager
     */ 
    public function getCookieManager();

}