<?php
namespace Yurun\Util\YurunHttp\Handler\Swoole;

use Yurun\Util\YurunHttp\Handler\Contract\IConnectionManager;

abstract class BaseConnectionManager implements IConnectionManager
{
    /**
     * 连接对象
     *
     * @var \Swoole\Coroutine\Http\Client[]|\Swoole\Coroutine\Http2\Client[]
     */
    private $connections = [];

    /**
     * SSL 连接对象
     *
     * @var \Swoole\Coroutine\Http\Client[]|\Swoole\Coroutine\Http2\Client[]
     */
    private $sslConnections = [];

    /**
     * 获取连接
     *
     * @param string $host
     * @param int $port
     * @param bool $ssl
     * @return \Swoole\Coroutine\Http\Client|\Swoole\Coroutine\Http2\Client
     */
    public function getConnection($host, $port, $ssl)
    {
        $key = $this->getKey($host, $port);
        if($ssl)
        {
            $sslConnections = &$this->sslConnections;
            if(!isset($sslConnections[$key]))
            {
                $sslConnections[$key] = $this->createConnection($host, $port, $ssl);
            }
            return $sslConnections[$key];
        }
        else
        {
            $connections = &$this->connections;
            if(!isset($connections[$key]))
            {
                $connections[$key] = $this->createConnection($host, $port, $ssl);
            }
            return $connections[$key];
        }
    }

    /**
     * 将指定连接从本管理器中移除，但不会关闭该连接
     *
     * @param string $host
     * @param int $port
     * @return \Swoole\Coroutine\Http\Client|\Swoole\Coroutine\Http2\Client|bool
     */
    public function removeConnection($host, $port, $ssl)
    {
        $key = $this->getKey($host, $port);
        if($ssl)
        {
            $sslConnections = &$this->sslConnections;
            if(isset($sslConnections[$key]))
            {
                $connection = $sslConnections[$key];
                unset($sslConnections[$key]);
                return $connection;
            }
            else
            {
                return false;
            }
        }
        else
        {
            $connections = &$this->connections;
            if(isset($connections[$key]))
            {
                $connection = $connections[$key];
                unset($connections[$key]);
                return $connection;
            }
            else
            {
                return false;
            }
        }
    }

    private function getKey($host, $port)
    {
        return $host . ':' . $port;
    }

    /**
     * 关闭指定连接
     *
     * @param string $host
     * @param int $port
     * @return bool
     */
    public function closeConnection($host, $port, $ssl)
    {
        $key = $this->getKey($host, $port);
        if($ssl)
        {
            $sslConnections = &$this->sslConnections;
            if(isset($sslConnections[$key]))
            {
                $sslConnections[$key]->close();
                unset($sslConnections[$key]);
                return true;
            }
            else
            {
                return false;
            }
        }
        else
        {
            $connections = &$this->connections;
            if(isset($connections[$key]))
            {
                $connections[$key]->close();
                unset($connections[$key]);
                return true;
            }
            else
            {
                return false;
            }
        }
    }

    /**
     * 关闭连接管理器
     *
     * @return void
     */
    public function close()
    {
        foreach($this->connections as $client)
        {
            $client->close();
        }
        foreach($this->sslConnections as $client)
        {
            $client->close();
        }
    }

}
