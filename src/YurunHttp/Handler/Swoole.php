<?php
namespace Yurun\Util\YurunHttp\Handler;

use Swoole\Coroutine\Http\Client;
use Swoole\Http2\Request as Http2Request;
use Swoole\Coroutine\Http2\Client as Http2Client;
use Yurun\Util\YurunHttp\Http\Psr7\Uri;
use Yurun\Util\YurunHttp\Http\Response;
use Yurun\Util\YurunHttp\Traits\THandler;
use Yurun\Util\YurunHttp\Traits\TCookieManager;
use Yurun\Util\YurunHttp\Http\Psr7\Consts\MediaType;
use Yurun\Util\YurunHttp\Exception\WebSocketException;

class Swoole implements IHandler
{
    use TCookieManager, THandler;

    /**
     * Swoole 协程客户端对象
     *
     * @var \Swoole\Coroutine\Http\Client|\Swoole\Coroutine\Http2\Client
     */
    private $handler;

    /**
     * 请求结果
     *
     * @var \Yurun\Util\YurunHttp\Http\Response
     */
    private $result;

    /**
     * 请求内容
     * @var \Yurun\Util\YurunHttp\Http\Request
     */
    private $request;
    
    /**
     * 设置客户端参数
     * @var array
     */
    private $settings = [];

    /**
     * 是否为 http2 请求
     *
     * @var bool
     */
    private $isHttp2;

    /**
     * Http2 请求对象
     *
     * @var \Swoole\Http2\Request
     */
    private $http2Request;

    /**
     * 上一个请求是否为SSL
     *
     * @var bool
     */
    private $lastSSL;

    public function __construct()
    {
        $this->initCookieManager();
    }

    public function __destruct()
    {
        if($this->handler)
        {
            $this->handler->close();
            $this->handler = null;
        }
    }

    /**
     * 发送请求
     * @param \Yurun\Util\YurunHttp\Http\Request $request
     * @return void
     */
    public function send($request)
    {
        $this->request = $request;
        if([] !== ($queryParams = $this->request->getQueryParams()))
        {
            $this->request = $this->request->withUri($this->request->getUri()->withQuery(http_build_query($queryParams, '', '&')));
        }
        $uri = $this->request->getUri();
        $isLocation = false;
        $count = 0;
        $statusCode = 0;
        if($this->isHttp2 = '2.0' === $this->request->getProtocolVersion())
        {
            $this->http2Request = new Http2Request;
        }
        $isWebSocket = $request->getAttribute('__websocket');
        do{
            $retry = $this->request->getAttribute('retry', 0);
            for($i = 0; $i <= $retry; ++$i)
            {
                $this->settings = $this->request->getAttribute('options', []);
                // 实例化
                $host = $uri->getHost();
                $port = Uri::getServerPort($uri);
                $ssl = 'https' === $uri->getScheme();
                if(!$this->handler || $this->handler->host != $host || $this->handler->port != $port || $ssl !== $this->lastSSL)
                {
                    $this->lastSSL = $ssl;
                    if($this->handler)
                    {
                        $this->handler->close();
                    }
                    if($this->isHttp2)
                    {
                        $this->handler = new Http2Client($host, $port, $ssl);
                        if(!$this->handler->connect())
                        {
                            throw new \RuntimeException(sprintf('Http2 connect failed!errCode: %s, errMsg:%', $this->handler->errCode, socket_strerror($this->handler->errCode)));
                        }
                    }
                    else
                    {
                        $this->handler = new Client($host, $port, $ssl);
                        $this->handler->setDefer();
                    }
                }
                // method
                if($isLocation && in_array($statusCode, [301, 302, 303]))
                {
                    $method = 'GET';
                }
                else
                {
                    $method = $this->request->getMethod();
                }
                if($this->isHttp2)
                {
                    $this->http2Request->method = $method;
                }
                else
                {
                    $this->handler->setMethod($method);
                }
                // cookie
                $this->parseCookies();
                // body
                $hasFile = false;
                if(!$isLocation)
                {
                    $files = $this->request->getUploadedFiles();
                    $body = (string)$this->request->getBody();
                    if(!empty($files))
                    {
                        if($this->isHttp2)
                        {
                            throw new \RuntimeException('Http2 swoole handler does not support upload file');
                        }
                        $hasFile = true;
                        foreach($files as $name => $file)
                        {
                            $this->handler->addFile($file->getTempFileName(), $name, $file->getClientMediaType(), basename($file->getClientFilename()));
                        }
                        parse_str($body, $body);
                    }
                    if($this->isHttp2)
                    {
                        $this->http2Request->data = $body;
                    }
                    else
                    {
                        $this->handler->setData($body);
                    }
                }
                // 其它处理
                $this->parseSSL();
                $this->parseProxy();
                $this->parseNetwork();
                // 设置客户端参数
                if(!empty($this->settings))
                {
                    $this->handler->set($this->settings);
                }
                // headers
                $this->request = $this->request->withHeader('Host', Uri::getDomain($uri));
                if(!$hasFile && !$this->request->hasHeader('Content-Type'))
                {
                    $this->request = $this->request->withHeader('Content-Type', MediaType::APPLICATION_FORM_URLENCODED);
                }
                $headers = [];
                foreach($this->request->getHeaders() as $name => $value)
                {
                    $headers[$name] = implode(',', $value);
                }
                if($this->isHttp2)
                {
                    $this->http2Request->headers = $headers;
                }
                else
                {
                    $this->handler->setHeaders($headers);
                }
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
                    if($this->isHttp2)
                    {
                        throw new \RuntimeException('Http2 swoole handler does not support websocket');
                    }
                    if(!$this->handler->upgrade($path))
                    {
                        throw new WebSocketException(sprintf('WebSocket connect faled, error: %s, errorCode: %s', socket_strerror($this->handler->errCode), $this->handler->errCode), $this->handler->errCode);
                    }
                }
                else if(null === ($saveFilePath = $this->request->getAttribute('saveFilePath')))
                {
                    if($this->isHttp2)
                    {
                        $this->http2Request->path = $path;
                        $this->handler->send($this->http2Request);
                    }
                    else
                    {
                        $this->handler->execute($path);
                    }
                }
                else
                {
                    if($this->isHttp2)
                    {
                        throw new \RuntimeException('Http2 swoole handler does not support download file');
                    }
                    $this->handler->download($path, $saveFilePath);
                }
                $this->getResponse($isWebSocket);
                if(!$this->result)
                {
                    return;
                }
                $statusCode = $this->result->getStatusCode();
                // 状态码为5XX或者0才需要重试
                if(!(0 === $statusCode || (5 === (int)($statusCode/100))))
                {
                    break;
                }
            }
            if(!$isWebSocket && $statusCode >= 300 && $statusCode < 400 && $this->request->getAttribute('followLocation', true))
            {
                if(++$count <= ($maxRedirects = $this->request->getAttribute('maxRedirects', 10)))
                {
                    // 自己实现重定向
                    $uri = $this->parseRedirectLocation($this->result->getHeaderLine('location'), $uri);
                    $isLocation = true;
                    continue;
                }
                else
                {
                    $this->result = $this->result->withErrno(-1)
                                                 ->withError(sprintf('Maximum (%s) redirects followed', $maxRedirects));
                }
            }
            break;
        }while(true);
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
     * @return void
     */
    private function parseCookies()
    {
        $cookieParams = $this->request->getCookieParams();
        foreach($cookieParams as $name => $value)
        {
            $this->cookieManager->setCookie($name, $value);
        }
        $cookies = $this->cookieManager->getRequestCookies($this->request->getUri());
        if($this->isHttp2)
        {
            $cookie = [];
            foreach($cookies as $name => $value)
            {
                $cookie[] = $name . '=' . urlencode($value);
            }
            $this->request = $this->request->withHeader('cookie', implode(',', $cookie));
        }
        else
        {
            $this->handler->setCookies($cookies);
        }
    }

    /**
     * 获取响应对象
     *
     * @return \Yurun\Util\YurunHttp\Http\Response
     */
    private function getResponse($isWebSocket)
    {
        if($this->isHttp2)
        {
            if($this->request->getAttribute('http2_not_recv'))
            {
                return null;
            }
            else
            {
                $response = $this->handler->recv();
                $success = false !== $response;
                $this->result = new Response($success ? $response->data: '', $success ? $response->statusCode : 0);
                if($success)
                {
                    // headers
                    foreach($response->headers as $name => $value)
                    {
                        $this->result = $this->result->withHeader($name, $value);
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
                    $this->result = $this->result->withCookieOriginParams($cookies);
                }
            }
        }
        else
        {
            $success = $isWebSocket ? true : $this->handler->recv();
            $this->result = new Response((string)$this->handler->body, $this->handler->statusCode);
            if($success)
            {
                // headers
                foreach($this->handler->headers as $name => $value)
                {
                    $this->result = $this->result->withHeader($name, $value);
                }

                // cookies
                $cookies = [];
                if(isset($this->handler->set_cookie_headers))
                {
                    foreach($this->handler->set_cookie_headers as $value)
                    {
                        $cookieItem = $this->cookieManager->addSetCookie($value);
                        $cookies[$cookieItem->name] = (array)$cookieItem;
                    }
                }
                $this->result = $this->result->withCookieOriginParams($cookies);
            }
        }
        $this->result = $this->result->withError(socket_strerror($this->handler->errCode))
                                     ->withErrno($this->handler->errCode);
        return $this->result;
    }

    /**
     * 处理加密访问
     * @return void
     */
    private function parseSSL()
    {
        if($this->request->getAttribute('isVerifyCA', false))
        {
            $this->settings['ssl_verify_peer'] = true;
            $caCert = $this->request->getAttribute('caCert');
            if(null !== $caCert)
            {
                $this->settings['ssl_cafile'] = $caCert;
            }
        }
        else
        {
            $this->settings['ssl_verify_peer'] = false;
        }
        $certPath = $this->request->getAttribute('certPath', '');
        if('' !== $certPath)
        {
            $this->settings['ssl_cert_file'] = $certPath;
        }
        $keyPath = $this->request->getAttribute('keyPath' , '');
        if('' !== $keyPath)
        {
            $this->settings['ssl_key_file'] = $keyPath;
        }
    }

    /**
     * 处理代理
     * @return void
     */
    private function parseProxy()
    {
        if($this->request->getAttribute('useProxy', false))
        {
            $type = $this->request->getAttribute('proxy.type');
            switch($type)
            {
                case 'http':
                    $this->settings['http_proxy_host'] = $this->request->getAttribute('proxy.server');
                    $this->settings['http_proxy_port'] = $this->request->getAttribute('proxy.port');
                    $this->settings['http_proxy_user'] = $this->request->getAttribute('proxy.username', '');
                    $this->settings['http_proxy_password'] = $this->request->getAttribute('proxy.password', '');
                    break;
                case 'socks5':
                    $this->settings['socks5_host'] = $this->request->getAttribute('proxy.server');
                    $this->settings['socks5_port'] = $this->request->getAttribute('proxy.port');
                    $this->settings['socks5_username'] = $this->request->getAttribute('proxy.username', '');
                    $this->settings['socks5_password'] = $this->request->getAttribute('proxy.password', '');
                    break;
            }
        }
    }
    
    /**
     * 处理网络相关
     * @return void
     */
    private function parseNetwork()
    {
        // 用户名密码认证处理
        $username = $this->request->getAttribute('username');
        if(null != $username)
        {
            $auth = base64_encode($username . ':' . $this->request->getAttribute('password', ''));
            $this->request = $this->request->withHeader('Authorization', 'Basic ' . $auth);
        }
        // 超时
        $this->settings['timeout'] = $this->request->getAttribute('timeout', 30000) / 1000;
        // 长连接
        $this->settings['keep_alive'] = $this->request->getAttribute('keep_alive', true);
    }

    /**
     * 获取原始处理器对象
     *
     * @return mixed
     */
    public function getHandler()
    {
        return $this->handler;
    }

}