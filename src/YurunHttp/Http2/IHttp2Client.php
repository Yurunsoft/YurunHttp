<?php
namespace Yurun\Util\YurunHttp\Http2;

interface IHttp2Client
{
    /**
     * @param string $host
     * @param int $port
     * @param bool $ssl
     * @param mixed $handler
     */
    public function __construct($host, $port, $ssl, $handler = null);

    /**
     * 连接
     *
     * @return bool
     */
    public function connect();

    /**
     * 获取 Http Handler
     *
     * @return \Yurun\Util\YurunHttp\Handler\IHandler
     */ 
    public function getHttpHandler();

    /**
     * 关闭连接
     *
     * @return void
     */
    public function close();

    /**
     * 发送数据
     * 成功返回streamId，失败返回false
     *
     * @param \Yurun\Util\YurunHttp\Http\Request $request
     * @param bool $dropRecvResponse
     * @return int|bool
     */
    public function send($request, $dropRecvResponse = false);

    /**
     * 接收数据
     *
     * @param int|null $streamId 默认不传为 -1 时则监听服务端推送
     * @param double|null $timeout 超时时间，单位：秒。默认为 null 不限制
     * @return \Yurun\Util\YurunHttp\Http\Response|bool
     */
    public function recv($streamId = -1, $timeout = null);

    /**
     * 是否已连接
     *
     * @return boolean
     */
    public function isConnected();

    /**
     * Get 主机名
     *
     * @return string
     */ 
    public function getHost();

    /**
     * Get 端口
     *
     * @return int
     */ 
    public function getPort();

    /**
     * Get 是否使用 ssl
     *
     * @return bool
     */ 
    public function isSSL();

    /**
     * 获取正在接收的流数量
     *
     * @return int
     */
    public function getRecvingCount();

}
