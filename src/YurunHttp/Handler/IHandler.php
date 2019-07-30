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
     * 连接 WebSocket
     *
     * @param \Yurun\Util\YurunHttp\Http\Request $request
     * @param \Yurun\Util\YurunHttp\WebSocket\IWebSocketClient $websocketClient
     * @return \Yurun\Util\YurunHttp\WebSocket\IWebSocketClient
     */
    public function websocket($request, $websocketClient = null);

    /**
     * Get cookie 管理器
     *
     * @return  \Yurun\Util\YurunHttp\Cookie\CookieManager
     */ 
    public function getCookieManager();

    /**
     * 获取原始处理器对象
     *
     * @return mixed
     */
    public function getHandler();

}