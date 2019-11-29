<?php
namespace Yurun\Util\YurunHttp\Http2;

use Swoole\Coroutine;
use Swoole\Coroutine\Channel;
use Yurun\Util\YurunHttp\Http\Psr7\Uri;

class SwooleClient implements IHttp2Client
{
    /**
     * 主机名
     *
     * @var string
     */
    private $host;

    /**
     * 端口
     *
     * @var int
     */
    private $port;

    /**
     * 是否使用 ssl
     *
     * @var bool
     */
    private $ssl;

    /**
     * Swoole 协程客户端对象
     *
     * @var \Yurun\Util\YurunHttp\Handler\Swoole $handler
     */
    private $handler;

    /**
     * Swoole http2 客户端
     *
     * @var \Swoole\Coroutine\Http2\Client
     */
    private $http2Client;

    /**
     * 接收的频道集合
     *
     * @var \Swoole\Coroutine\Channel[]
     */
    private $recvChannels = [];

    /**
     * @param string $host
     * @param int $port
     * @param bool $ssl
     * @param \Yurun\Util\YurunHttp\Handler\Swoole $handler
     */
    public function __construct($host, $port, $ssl, $handler = null)
    {
        $this->host = $host;
        $this->port = $port;
        $this->ssl = $ssl;
        if($handler)
        {
            $this->handler = $handler;
        }
        else
        {
            $this->handler = new \Yurun\Util\YurunHttp\Handler\Swoole;
        }
    }

    /**
     * 连接
     *
     * @return bool
     */
    public function connect()
    {
        $client = $this->handler->getHttp2ConnectionManager()->getConnection($this->host, $this->port, $this->ssl);
        if($client)
        {
            $this->http2Client = $client;
            $this->startRecvCo();
            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     * 获取 Http Handler
     *
     * @return \Yurun\Util\YurunHttp\Handler\IHandler
     */ 
    public function getHttpHandler()
    {
        return $this->handler;
    }

    /**
     * 关闭连接
     *
     * @return void
     */
    public function close()
    {
        $this->http2Client = null;
        $this->handler->getHttp2ConnectionManager()->closeConnection($this->host, $this->port, $this->ssl);
        foreach($this->recvChannels as $channel)
        {
            $channel->close();
        }
        $this->recvChannels = [];
    }

    /**
     * 发送数据
     * 成功返回streamId，失败返回false
     *
     * @param \Yurun\Util\YurunHttp\Http\Request $request
     * @return int|bool
     */
    public function send($request)
    {
        if('2.0' !== $request->getProtocolVersion())
        {
            $request = $request->withProtocolVersion('2.0');
        }
        $uri = $request->getUri();
        if($this->host != $uri->getHost() || $this->port != Uri::getServerPort($uri) || $this->ssl != ('https' === $uri->getScheme()))
        {
            throw new \RuntimeException(sprintf('Current http2 connection instance just support %s://%s:%s, does not support %s', $this->ssl ? 'https' : 'http', $this->host, $this->port, $uri->__toString()));
        }
        $this->handler->buildRequest($request, $this->http2Client, $http2Request);
        $result = $this->http2Client->send($http2Request);
        if(!$result)
        {
            $this->close();
        }
        return $result;
    }

    /**
     * 接收数据
     *
     * @param int|null $streamId 默认不传为 -1 时则监听服务端推送
     * @param double|null $timeout 超时时间，单位：秒。默认为 null 不限制
     * @return \Yurun\Util\YurunHttp\Http\Response|bool
     */
    public function recv($streamId = -1, $timeout = null)
    {
        if(isset($this->recvChannels[$streamId]))
        {
            throw new \RuntimeException(sprintf('Cannot listen to stream #%s repeatedly', $streamId));
        }
        $this->recvChannels[$streamId] = $channel = new Channel(1);
        $swooleResponse = $channel->pop($timeout);
        unset($this->recvChannels[$streamId]);
        $channel->close();
        $response = $this->handler->buildHttp2Response($swooleResponse);
        return $response;
    }

    /**
     * 是否已连接
     * 
     * @return boolean
     */
    public function isConnected()
    {
        return null !== $this->http2Client;
    }

    /**
     * 开始接收协程
     * 成功返回协程ID
     *
     * @return int|bool
     */
    private function startRecvCo()
    {
        if(!$this->isConnected())
        {
            return false;
        }
        return Coroutine::create(function(){
            while($this->isConnected())
            {
                $swooleResponse = $this->http2Client->recv();
                if(!$swooleResponse)
                {
                    $this->close();
                    return;
                }
                $streamId = $swooleResponse->streamId;
                if(isset($this->recvChannels[$streamId]) || isset($this->recvChannels[$streamId = -1]))
                {
                    $this->recvChannels[$streamId]->push($swooleResponse);
                }
            }
        });
    }

    /**
     * Get 主机名
     *
     * @return string
     */ 
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Get 端口
     *
     * @return int
     */ 
    public function getPort()
    {
        return $this->port;
    }

    /**
     * Get 是否使用 ssl
     *
     * @return bool
     */ 
    public function isSSL()
    {
        return $this->ssl;
    }

    /**
     * 获取正在接收的流数量
     *
     * @return int
     */
    public function getRecvingCount()
    {
        return count($this->recvChannels);
    }

}
