<?php
namespace Yurun\Util\YurunHttp\Handler;

use Yurun\Util\YurunHttp\Http\Psr7\Uri;
use Yurun\Util\YurunHttp\Http\Response;
use Yurun\Util\YurunHttp\FormDataBuilder;
use Yurun\Util\YurunHttp\Traits\TCookieManager;
use Yurun\Util\YurunHttp\Http\Psr7\Consts\MediaType;
use Yurun\Util\YurunHttp\Traits\THandler;

class Curl implements IHandler
{
    use TCookieManager, THandler;

    /**
     * 请求结果
     *
     * @var \Yurun\Util\YurunHttp\Http\Response
     */
    private $result;
    
    /**
     * curl 句柄
     * @var resource
     */
    private $handler;

    /**
     * 请求内容
     * @var \Yurun\Util\YurunHttp\Http\Request
     */
    private $request;

    /**
     * curl 请求结果
     * @var string
     */
    private $curlResult;
    
    /**
     * 保存到的文件的句柄
     * @var resource
     */
    private $saveFileFp;

    /**
     * 下载文件上时，header 写入的文件句柄
     *
     * @var resource
     */
    private $headerFileFp;

    /**
     * 代理认证方式
     */
    public static $proxyAuths = array(
        'basic' =>  CURLAUTH_BASIC,
        'ntlm'  =>  CURLAUTH_NTLM
    );

    /**
     * 代理类型
     */
    public static $proxyType = array(
        'http'      =>  CURLPROXY_HTTP,
        'socks4'    =>  CURLPROXY_SOCKS4,
        'socks4a'   =>  6, // CURLPROXY_SOCKS4A
        'socks5'    =>  CURLPROXY_SOCKS5,
    );

    public function __construct()
    {
        $this->initCookieManager();
    }

    public function __destruct()
    {
        if($this->handler)
        {
            curl_close($this->handler);
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
        if(!$this->handler)
        {
            $this->handler = curl_init();
            $options = [
                // 返回内容
                CURLOPT_RETURNTRANSFER  => true,
                // 返回header
                CURLOPT_HEADER          => true,
                // 保存cookie
                CURLOPT_COOKIEJAR       => 'php://memory',
            ];
        }
        // 自动重定向
        $options[CURLOPT_MAXREDIRS] = $this->request->getAttribute('maxRedirects', 10);

        // 发送内容
        $files = $this->request->getUploadedFiles();
        $body = (string)$this->request->getBody();
        
        if(!empty($files))
        {
            $body = FormDataBuilder::build($body, $files, $boundary);
            $this->request = $this->request = $this->request->withHeader('Content-Type', MediaType::MULTIPART_FORM_DATA . '; boundary=' . $boundary);
        }
        // 自动解压缩支持
        $acceptEncoding = $this->request->getHeaderLine('Accept-Encoding');
        if('' !== $acceptEncoding)
        {
            $options[CURLOPT_ENCODING] = $acceptEncoding;
        }
        else
        {
            $options[CURLOPT_ENCODING] = '';
        }
        curl_setopt_array($this->handler, $options);
        $this->parseSSL();
        $this->parseOptions();
        $this->parseProxy();
        $this->parseHeaders();
        $this->parseCookies();
        $this->parseNetwork();
        $count = 0;
        if([] !== ($queryParams = $this->request->getQueryParams()))
        {
            $this->request = $this->request->withUri($this->request->getUri()->withQuery(http_build_query($queryParams, '', '&')));
        }
        $uri = $this->request->getUri();
        $isLocation = false;
        $statusCode = 0;
        $lastMethod = null;
        $copyHandler = curl_copy_handle($this->handler);
        do{
            $requestOptions = [
                CURLOPT_URL     =>  (string)$uri,
            ];
            // 请求方法
            if($isLocation && in_array($statusCode, [301, 302, 303]))
            {
                $method = 'GET';
            }
            else
            {
                $method = $this->request->getMethod();
            }
            if('GET' !== $method)
            {
                $requestOptions[CURLOPT_POSTFIELDS] = $body;
            }
            $requestOptions[CURLOPT_CUSTOMREQUEST] = $method;
            if($lastMethod && 'GET' !== $lastMethod && 'GET' === $method)
            {
                $this->handler = curl_copy_handle($copyHandler);
            }
            $lastMethod = $requestOptions[CURLOPT_CUSTOMREQUEST];
            curl_setopt_array($this->handler, $requestOptions);
            $retry = $this->request->getAttribute('retry', 0);
            for($i = 0; $i <= $retry; ++$i)
            {
                $this->curlResult = curl_exec($this->handler);
                // 下载文件特别处理 header
                if($this->headerFileFp)
                {
                    fseek($this->headerFileFp, 0);
                    $length = curl_getinfo($this->handler, CURLINFO_HEADER_SIZE);
                    $this->curlResult = fread($this->headerFileFp, $length);
                }
                $this->getResponse();
                $statusCode = $this->result->getStatusCode();
                // 状态码为5XX或者0才需要重试
                if(!(0 === $statusCode || (5 === (int)($statusCode/100))))
                {
                    break;
                }
            }
            if($this->request->getAttribute('followLocation', true) && ($statusCode >= 300 && $statusCode < 400))
            {
                if(++$count <= ($maxRedirects = $this->request->getAttribute('maxRedirects', 10)))
                {
                    $isLocation = true;
                    $uri = $this->parseRedirectLocation($this->result->getHeaderLine('location'), $uri);
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
        // 关闭保存至文件的句柄
        if(null !== $this->saveFileFp)
        {
            fclose($this->saveFileFp);
            $this->saveFileFp = null;
        }
        if(null !== $this->headerFileFp)
        {
            fclose($this->headerFileFp);
            $this->headerFileFp = null;
        }
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
     * 获取响应对象
     *
     * @return \Yurun\Util\YurunHttp\Http\Response
     */
    private function getResponse()
    {
        // 分离header和body
        $headerSize = curl_getinfo($this->handler, CURLINFO_HEADER_SIZE);
        $headerContent = substr($this->curlResult, 0, $headerSize);
        $body = substr($this->curlResult, $headerSize);
        // PHP 7.0.0开始substr()的 string 字符串长度与 start 相同时将返回一个空字符串。在之前的版本中，这种情况将返回 FALSE 。
        if(false === $body)
        {
            $body = '';
        }

        // body
        $this->result = new Response($body, curl_getinfo($this->handler, CURLINFO_HTTP_CODE));

        // headers
        $rawHeaders = explode("\r\n\r\n", trim($headerContent));
        $requestCount = count($rawHeaders);
        if($requestCount > 0)
        {
            $headers = $this->parseHeaderOneRequest($rawHeaders[$requestCount - 1]);
            foreach($headers as $name => $value)
            {
                $this->result = $this->result->withAddedHeader($name, $value);
            }
        }
        
        // cookies
        $cookies = [];
        $count = preg_match_all('/set-cookie\s*:\s*([^\r\n]+)/i', $headerContent, $matches);
        for($i = 0; $i < $count; ++$i)
        {
            $cookieItem = $this->cookieManager->addSetCookie($matches[1][$i]);
            $cookies[$cookieItem->name] = (array)$cookieItem;
        }
        $this->result = $this->result->withCookieOriginParams($cookies)
                                    ->withError(curl_error($this->handler))
                                    ->withErrno(curl_errno($this->handler));
    }
    
    /**
     * parseHeaderOneRequest
     * @param string $piece 
     * @return array
     */
    private function parseHeaderOneRequest($piece)
    {
        $tmpHeaders = array();
        $lines = explode("\r\n", $piece);
        $linesCount = count($lines);
        //从1开始，第0行包含了协议信息和状态信息，排除该行
        for($i = 1; $i < $linesCount; ++$i)
        {
            $line = trim($lines[$i]);
            if(empty($line) || strstr($line, ':') == false)
            {
                continue;
            }
            list($key, $value) = explode(':', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if(isset($tmpHeaders[$key]))
            {
                if(is_array($tmpHeaders[$key]))
                {
                    $tmpHeaders[$key][] = $value;
                }
                else
                {
                    $tmp = $tmpHeaders[$key];
                    $tmpHeaders[$key] = array(
                        $tmp,
                        $value,
                    );
                }
            }
            else
            {
                $tmpHeaders[$key] = $value;
            }
        }
        return $tmpHeaders;
    }

    /**
     * 处理加密访问
     * @return void
     */
    private function parseSSL()
    {
        if($this->request->getAttribute('isVerifyCA', false))
        {
            curl_setopt_array($this->handler, array(
                CURLOPT_SSL_VERIFYPEER    => true,
                CURLOPT_CAINFO            => $this->request->getAttribute('caCert'),
                CURLOPT_SSL_VERIFYHOST    => 2,
            ));
        }
        else
        {
            curl_setopt_array($this->handler, array(
                CURLOPT_SSL_VERIFYPEER    => false,
                CURLOPT_SSL_VERIFYHOST    => 0,
            ));
        }
        $certPath = $this->request->getAttribute('certPath', '');
        if('' !== $certPath)
        {
            curl_setopt_array($this->handler, array(
                CURLOPT_SSLCERT         => $certPath,
                CURLOPT_SSLCERTPASSWD   => $this->request->getAttribute('certPassword'),
                CURLOPT_SSLCERTTYPE     => $this->request->getAttribute('certType', 'pem'),
            ));
        }
        $keyPath = $this->request->getAttribute('keyPath', '');
        if('' !== $keyPath)
        {
            curl_setopt_array($this->handler, array(
                CURLOPT_SSLKEY          => $keyPath,
                CURLOPT_SSLKEYPASSWD    => $this->request->getAttribute('keyPassword'),
                CURLOPT_SSLKEYTYPE      => $this->request->getAttribute('keyType', 'pem'),
            ));
        }
    }
    
    /**
     * 处理设置项
     * @return void
     */
    private function parseOptions()
    {
        curl_setopt_array($this->handler, $this->request->getAttribute('options', []));
        // 请求结果保存为文件
        if(null !== ($saveFilePath = $this->request->getAttribute('saveFilePath')))
        {
            $last = substr($saveFilePath, -1, 1);
            if('/' === $last || '\\' === $last)
            {
                // 自动获取文件名
                $saveFilePath .= basename($this->url);
            }
            $this->saveFileFp = fopen($saveFilePath, $this->request->getAttribute('saveFileMode', 'w+'));
            $this->headerFileFp = fopen('php://memory', 'w+');
            curl_setopt_array($this->handler, array(
                CURLOPT_HEADER          => false,
                CURLOPT_RETURNTRANSFER  => false,
                CURLOPT_FILE            => $this->saveFileFp,
                CURLOPT_WRITEHEADER     => $this->headerFileFp,
            ));
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
            $type = $this->request->getAttribute('proxy.type', 'http');
            curl_setopt_array($this->handler, array(
                CURLOPT_PROXYAUTH    => self::$proxyAuths[$this->request->getAttribute('proxy.auth', 'basic')],
                CURLOPT_PROXY        => $this->request->getAttribute('proxy.server'),
                CURLOPT_PROXYPORT    => $this->request->getAttribute('proxy.port'),
                CURLOPT_PROXYUSERPWD => $this->request->getAttribute('proxy.username', '') . ':' . $this->request->getAttribute('proxy.password', ''),
                CURLOPT_PROXYTYPE    => 'socks5' === $type ? (defined('CURLPROXY_SOCKS5_HOSTNAME') ? CURLPROXY_SOCKS5_HOSTNAME : self::$proxyType[$type]) : self::$proxyType[$type],
            ));
        }
    }
    
    /**
     * 处理headers
     * @return void
     */
    private function parseHeaders()
    {
        curl_setopt($this->handler, CURLOPT_HTTPHEADER, $this->parseHeadersFormat());
    }
    
    /**
     * 处理成CURL可以识别的headers格式
     * @return array 
     */
    private function parseHeadersFormat()
    {
        $headers = array();
        foreach($this->request->getHeaders() as $name => $value)
        {
            $headers[] = $name . ':' . implode(',', $value);
        }
        return $headers;
    }

    /**
     * 处理cookie
     * @return void
     */
    private function parseCookies()
    {
        foreach($this->request->getCookieParams() as $name => $value)
        {
            $this->cookieManager->setCookie($name, $value);
        }
        $cookie = $this->cookieManager->getRequestCookieString($this->request->getUri());
        curl_setopt($this->handler, CURLOPT_COOKIE, $cookie);
    }
    
    /**
     * 处理网络相关
     * @return void
     */
    private function parseNetwork()
    {
        // 用户名密码处理
        $username = $this->request->getAttribute('username');
        if(null != $username)
        {
            $userPwd = $username . ':' . $this->request->getAttribute('password', '');
        }
        else
        {
            $userPwd = '';
        }
        curl_setopt_array($this->handler, array(
            // 连接超时
            CURLOPT_CONNECTTIMEOUT_MS       => $this->request->getAttribute('connectTimeout', 30000),
            // 总超时
            CURLOPT_TIMEOUT_MS              => $this->request->getAttribute('timeout', 0),
            // 下载限速
            CURLOPT_MAX_RECV_SPEED_LARGE    => $this->request->getAttribute('downloadSpeed'),
            // 上传限速
            CURLOPT_MAX_SEND_SPEED_LARGE    => $this->request->getAttribute('uploadSpeed'),
            // 连接中用到的用户名和密码
            CURLOPT_USERPWD                 => $userPwd,
        ));
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
        throw new \RuntimeException('Curl Handler does not support WebSocket');
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