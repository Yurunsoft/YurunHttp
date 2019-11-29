<?php
namespace Yurun\Util\YurunHttp\Handler;

use Yurun\Util\YurunHttp;
use Yurun\Util\YurunHttp\Http\Psr7\Uri;
use Yurun\Util\YurunHttp\Http\Response;
use Swoole\Http2\Request as Http2Request;
use Yurun\Util\YurunHttp\Traits\THandler;
use Yurun\Util\YurunHttp\Traits\TCookieManager;
use Yurun\Util\YurunHttp\Http\Psr7\Consts\MediaType;
use Yurun\Util\YurunHttp\Exception\WebSocketException;
use Yurun\Util\YurunHttp\Handler\Swoole\HttpConnectionManager;
use Yurun\Util\YurunHttp\Handler\Swoole\Http2ConnectionManager;

class Swoole implements IHandler
{
    use TCookieManager, THandler;

    /**
     * Http 连接管理器
     *
     * @var \Yurun\Util\YurunHttp\Handler\Swoole\HttpConnectionManager
     */
    private $httpConnectionManager;

    /**
     * Http2 连接管理器
     *
     * @var \Yurun\Util\YurunHttp\Handler\Swoole\Http2ConnectionManager
     */
    private $http2ConnectionManager;

    /**
     * 请求结果
     *
     * @var \Yurun\Util\YurunHttp\Http\Response
     */
    private $result;

    /**
     * 本 Handler 默认的 User-Agent
     *
     * @var string
     */
    private static $defaultUA;

    public function __construct()
    {
        if(null === static::$defaultUA)
        {
            static::$defaultUA = sprintf('Mozilla/5.0 YurunHttp/%s Swoole/%s', YurunHttp::VERSION, defined('SWOOLE_VERSION') ? SWOOLE_VERSION : 'unknown');
        }
        $this->initCookieManager();
        $this->httpConnectionManager = new HttpConnectionManager;
        $this->http2ConnectionManager = new Http2ConnectionManager;
    }

    public function __destruct()
    {
        $this->httpConnectionManager->close();
        $this->http2ConnectionManager->close();
    }

    /**
     * 构建请求
     *
     * @param \Yurun\Util\YurunHttp\Http\Request $request
     * @param \Swoole\Coroutine\Http\Client|\Swoole\Coroutine\Http2\Client $connection
     * @param \Swoole\Coroutine\Http2\Request $http2Request
     * @return void
     */
    public function buildRequest($request, $connection, &$http2Request)
    {
        if($isHttp2 = '2.0' === $request->getProtocolVersion())
        {
            $http2Request = new Http2Request;
        }
        else
        {
            $http2Request = null;
        }
        $uri = $request->getUri();
        // method
        if($isHttp2)
        {
            $http2Request->method = $request->getMethod();
        }
        else
        {
            $connection->setMethod($request->getMethod());
        }
        // cookie
        $this->parseCookies($request, $connection, $http2Request);
        // body
        $hasFile = false;
        $redirectCount = $request->getAttribute('__redirectCount', 0);
        if($redirectCount <= 0)
        {
            $files = $request->getUploadedFiles();
            $body = (string)$request->getBody();
            if(!empty($files))
            {
                if($isHttp2)
                {
                    throw new \RuntimeException('Http2 swoole handler does not support upload file');
                }
                $hasFile = true;
                foreach($files as $name => $file)
                {
                    $connection->addFile($file->getTempFileName(), $name, $file->getClientMediaType(), basename($file->getClientFilename()));
                }
                parse_str($body, $body);
            }
            if($isHttp2)
            {
                $http2Request->data = $body;
            }
            else
            {
                $connection->setData($body);
            }
        }
        // 其它处理
        $this->parseSSL($request);
        $this->parseProxy($request);
        $this->parseNetwork($request);
        // 设置客户端参数
        $settings = $request->getAttribute('options', []);
        if($settings)
        {
            $connection->set($settings);
        }
        // headers
        $request = $request->withHeader('Host', Uri::getDomain($uri));
        if(!$hasFile && !$request->hasHeader('Content-Type'))
        {
            $request = $request->withHeader('Content-Type', MediaType::APPLICATION_FORM_URLENCODED);
        }
        if(!$request->hasHeader('User-Agent'))
        {
            $request = $request->withHeader('User-Agent', $request->getAttribute('userAgent', static::$defaultUA));
        }
        $headers = [];
        foreach($request->getHeaders() as $name => $value)
        {
            $headers[$name] = implode(',', $value);
        }
        if($isHttp2)
        {
            $http2Request->headers = $headers;
        }
        else
        {
            $connection->setHeaders($headers);
        }
    }

    /**
     * 发送请求
     * @param \Yurun\Util\YurunHttp\Http\Request $request
     * @return bool
     */
    public function send($request)
    {
        if([] !== ($queryParams = $request->getQueryParams()))
        {
            $request = $request->withUri($request->getUri()->withQuery(http_build_query($queryParams, '', '&')));
        }
        $uri = $request->getUri();
        $isHttp2 = '2.0' === $request->getProtocolVersion();
        if($isHttp2)
        {
            $connection = $this->http2ConnectionManager->getConnection($uri->getHost(), Uri::getServerPort($uri), 'https' === $uri->getScheme());
        }
        else
        {
            $connection = $this->httpConnectionManager->getConnection($uri->getHost(), Uri::getServerPort($uri), 'https' === $uri->getScheme());
            $connection->setDefer(true);
        }
        $redirectCount = $request->getAttribute('__redirectCount', 0);
        $statusCode = 0;
        $isWebSocket = $request->getAttribute('__websocket');
        $retry = $request->getAttribute('retry', 0);
        for($i = 0; $i <= $retry; ++$i)
        {
            // 构建
            $this->buildRequest($request, $connection, $http2Request);
            // 发送
            $path = $uri->getPath();
            if('' === $path)
            {
                $path = '/';
            }
            $query = $uri->getQuery();
            if('' !== $query)
            {
                $path .= '?' . $query;
            }
            if($isWebSocket)
            {
                if($isHttp2)
                {
                    throw new \RuntimeException('Http2 swoole handler does not support websocket');
                }
                if(!$connection->upgrade($path))
                {
                    throw new WebSocketException(sprintf('WebSocket connect faled, error: %s, errorCode: %s', socket_strerror($connection->errCode), $connection->errCode), $connection->errCode);
                }
            }
            else if(null === ($saveFilePath = $request->getAttribute('saveFilePath')))
            {
                if($isHttp2)
                {
                    $http2Request->path = $path;
                    $result = $connection->send($http2Request);
                }
                else
                {
                    $connection->execute($path);
                }
            }
            else
            {
                if($isHttp2)
                {
                    throw new \RuntimeException('Http2 swoole handler does not support download file');
                }
                $connection->download($path, $saveFilePath);
            }
            if($isHttp2 && $request->getAttribute('http2_not_recv'))
            {
                return $result;
            }
            $this->getResponse($connection, $isWebSocket, $isHttp2);
            $statusCode = $this->result->getStatusCode();
            // 状态码为5XX或者0才需要重试
            if(!(0 === $statusCode || (5 === (int)($statusCode/100))))
            {
                break;
            }
        }
        if(!$isWebSocket && $statusCode >= 300 && $statusCode < 400 && $request->getAttribute('followLocation', true))
        {
            if(++$redirectCount <= ($maxRedirects = $request->getAttribute('maxRedirects', 10)))
            {
                // 自己实现重定向
                $uri = $this->parseRedirectLocation($this->result->getHeaderLine('location'), $uri);
                if(in_array($statusCode, [301, 302, 303]))
                {
                    $method = 'GET';
                }
                else
                {
                    $method = $request->getMethod();
                }
                return $this->send($request->withMethod($method)->withUri($uri)->withAttribute('__redirectCount', $redirectCount));
            }
            else
            {
                $this->result = $this->result->withErrno(-1)
                                                ->withError(sprintf('Maximum (%s) redirects followed', $maxRedirects));
                return false;
            }
        }
        return true;
    }

    /**
     * 连接 WebSocket
     *
     * @param \Yurun\Util\YurunHttp\Http\Request $request
     * @param \Yurun\Util\YurunHttp\WebSocket\IWebSocketClient $websocketClient
     * @return \Yurun\Util\YurunHttp\WebSocket\IWebSocketClient
     */
    public function websocket($request, $websocketClient = null)
    {
        if(!$websocketClient)
        {
            $websocketClient = new \Yurun\Util\YurunHttp\WebSocket\Swoole;
        }
        $this->send($request->withAttribute('__websocket', true));
        $websocketClient->init($this, $request, $this->result);
        return $websocketClient;
    }

    /**
     * 接收请求
     * @return \Yurun\Util\YurunHttp\Http\Response
     */
    public function recv()
    {
        return $this->result;
    }

    /**
     * 处理cookie
     * 
     * @param \Yurun\Util\YurunHttp\Http\Request $request
     * @param mixed $connection
     * @param \Swoole\Coroutine\Http2\Request $http2Request
     * @return void
     */
    private function parseCookies(&$request, $connection, $http2Request)
    {
        $cookieParams = $request->getCookieParams();
        foreach($cookieParams as $name => $value)
        {
            $this->cookieManager->setCookie($name, $value);
        }
        $cookies = $this->cookieManager->getRequestCookies($request->getUri());
        if($http2Request)
        {
            $cookie = [];
            foreach($cookies as $name => $value)
            {
                $cookie[] = $name . '=' . urlencode($value);
            }
            $request = $request->withHeader('cookie', implode(',', $cookie));
        }
        else
        {
            $connection->setCookies($cookies);
        }
    }

    /**
     * 构建 Http2 Response
     *
     * @param \Swoole\Http2\Response|bool $response
     * @return \Yurun\Util\YurunHttp\Http\Response
     */
    public function buildHttp2Response($response)
    {
        $success = false !== $response;
        $result = new Response($success ? $response->data: '', $success ? $response->statusCode : 0);
        if($success)
        {
            // streamId
            $result = $result->withStreamId($response->streamId);

            // headers
            foreach($response->headers as $name => $value)
            {
                $result = $result->withHeader($name, $value);
            }

            // cookies
            $cookies = [];
            if(isset($response->set_cookie_headers))
            {
                foreach($response->set_cookie_headers as $value)
                {
                    $cookieItem = $this->cookieManager->addSetCookie($value);
                    $cookies[$cookieItem->name] = (array)$cookieItem;
                }
            }
            $result = $result->withCookieOriginParams($cookies);
        }
        return $result;
    }

    /**
     * 获取响应对象
     *
     * @return \Yurun\Util\YurunHttp\Http\Response
     */
    private function getResponse($connection, $isWebSocket, $isHttp2)
    {
        if($isHttp2)
        {
            $response = $connection->recv();
            $this->result = $this->buildHttp2Response($response);
        }
        else
        {
            $success = $isWebSocket ? true : $connection->recv();
            $this->result = new Response((string)$connection->body, $connection->statusCode);
            if($success)
            {
                // headers
                foreach($connection->headers as $name => $value)
                {
                    $this->result = $this->result->withHeader($name, $value);
                }

                // cookies
                $cookies = [];
                if(isset($connection->set_cookie_headers))
                {
                    foreach($connection->set_cookie_headers as $value)
                    {
                        $cookieItem = $this->cookieManager->addSetCookie($value);
                        $cookies[$cookieItem->name] = (array)$cookieItem;
                    }
                }
                $this->result = $this->result->withCookieOriginParams($cookies);
            }
        }
        $this->result = $this->result->withError(socket_strerror($connection->errCode))
                                     ->withErrno($connection->errCode);
        return $this->result;
    }

    /**
     * 处理加密访问
     * 
     * @param \Yurun\Util\YurunHttp\Http\Request $request
     * @return void
     */
    private function parseSSL(&$request)
    {
        $settings = $request->getAttribute('options', []);
        if($request->getAttribute('isVerifyCA', false))
        {
            $settings['ssl_verify_peer'] = true;
            $caCert =$request->getAttribute('caCert');
            if(null !== $caCert)
            {
                $settings['ssl_cafile'] = $caCert;
            }
        }
        else
        {
            $settings['ssl_verify_peer'] = false;
        }
        $certPath = $request->getAttribute('certPath', '');
        if('' !== $certPath)
        {
            $settings['ssl_cert_file'] = $certPath;
        }
        $keyPath = $request->getAttribute('keyPath' , '');
        if('' !== $keyPath)
        {
            $settings['ssl_key_file'] = $keyPath;
        }
        $request = $request->withAttribute('options', $settings);
    }

    /**
     * 处理代理
     * @param \Yurun\Util\YurunHttp\Http\Request $request
     * @return void
     */
    private function parseProxy(&$request)
    {
        $settings = $request->getAttribute('options', []);
        if($request->getAttribute('useProxy', false))
        {
            $type = $request->getAttribute('proxy.type');
            switch($type)
            {
                case 'http':
                    $settings['http_proxy_host'] = $request->getAttribute('proxy.server');
                    $settings['http_proxy_port'] = $request->getAttribute('proxy.port');
                    $settings['http_proxy_user'] = $request->getAttribute('proxy.username', '');
                    $settings['http_proxy_password'] = $request->getAttribute('proxy.password', '');
                    break;
                case 'socks5':
                    $settings['socks5_host'] = $request->getAttribute('proxy.server');
                    $settings['socks5_port'] = $request->getAttribute('proxy.port');
                    $settings['socks5_username'] = $request->getAttribute('proxy.username', '');
                    $settings['socks5_password'] = $request->getAttribute('proxy.password', '');
                    break;
            }
        }
        $request = $request->withAttribute('options', $settings);
    }
    
    /**
     * 处理网络相关
     * @param \Yurun\Util\YurunHttp\Http\Request $request
     * @return void
     */
    private function parseNetwork(&$request)
    {
        $settings = $request->getAttribute('options', []);
        // 用户名密码认证处理
        $username = $request->getAttribute('username');
        if(null != $username)
        {
            $auth = base64_encode($username . ':' . $request->getAttribute('password', ''));
            $request = $request->withHeader('Authorization', 'Basic ' . $auth);
        }
        // 超时
        $settings['timeout'] = $request->getAttribute('timeout', 30000) / 1000;
        // 长连接
        $settings['keep_alive'] = $request->getAttribute('keep_alive', true);
        $request = $request->withAttribute('options', $settings);
    }

    /**
     * 获取原始处理器对象
     *
     * @return mixed
     */
    public function getHandler()
    {
        return null;
    }


    /**
     * Get http 连接管理器
     *
     * @return \Yurun\Util\YurunHttp\Handler\Swoole\HttpConnectionManager
     */ 
    public function getHttpConnectionManager()
    {
        return $this->httpConnectionManager;
    }

    /**
     * Get http2 连接管理器
     *
     * @return \Yurun\Util\YurunHttp\Handler\Swoole\Http2ConnectionManager
     */ 
    public function getHttp2ConnectionManager()
    {
        return $this->http2ConnectionManager;
    }
}