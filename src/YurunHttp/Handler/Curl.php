<?php
namespace Yurun\Util\YurunHttp\Handler;

use Yurun\Util\YurunHttp\Http\Response;
use Yurun\Util\YurunHttp;
use Yurun\Util\YurunHttp\FormDataBuilder;
use Yurun\Util\YurunHttp\Http\Psr7\Consts\MediaType;


class Curl implements IHandler
{
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
	 * 代理认证方式
	 */
	public static $proxyAuths = array(
		'basic'		=>	CURLAUTH_BASIC,
		'ntlm'		=>	CURLAUTH_NTLM
	);

	/**
	 * 代理类型
	 */
	public static $proxyType = array(
		'http'		=>	CURLPROXY_HTTP,
		'socks4'	=>	CURLPROXY_SOCKS4,
		'socks4a'	=>	6,	// CURLPROXY_SOCKS4A
		'socks5'	=>	CURLPROXY_SOCKS5,
	);

    /**
     * 发送请求
     * @param \Yurun\Util\YurunHttp\Http\Request $request
     * @return void
     */
    public function send($request)
    {
		$this->request = $request;
		$this->handler = curl_init();
		$tempDir = $this->request->getAttribute('tempDir');
		$cookieFileName = tempnam(null === $tempDir ? sys_get_temp_dir() : $tempDir, '');
        $files = $this->request->getUploadedFiles();
		$body = (string)$this->request->getBody();
		if(isset($files[0]))
		{
			$body = FormDataBuilder::build($body, $files, $boundary);
			$this->request = $this->request = $this->request->withHeader('Content-Type', MediaType::MULTIPART_FORM_DATA . '; boundary=' . $boundary);
		}
		$options = [
			// 请求方法
			CURLOPT_CUSTOMREQUEST	=> $this->request->getMethod(),
			// 返回内容
			CURLOPT_RETURNTRANSFER	=> true,
			// 返回header
			CURLOPT_HEADER			=> true,
			// 发送内容
			CURLOPT_POSTFIELDS		=> $body,
			// 保存cookie
			CURLOPT_COOKIEFILE		=> $cookieFileName,
			CURLOPT_COOKIEJAR		=> $cookieFileName,
			// 自动重定向
			CURLOPT_FOLLOWLOCATION	=> $this->request->getAttribute('customLocation', false) ? false : $this->request->getAttribute('followLocation', true),
			// 最大重定向次数
			CURLOPT_MAXREDIRS		=> $this->request->getAttribute('maxRedirects', 10),
        ];
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
		$url = (string)$this->request->getUri();
		do{
			curl_setopt($this->handler, CURLOPT_URL, $url);
			$retry = $this->request->getAttribute('retry', 0);
			for($i = 0; $i <= $retry; ++$i)
			{
				$this->curlResult = curl_exec($this->handler);
				$this->getResponse();
				$statusCode = $this->result->getStatusCode();
				// 状态码为5XX或者0才需要重试
				if(!(0 === $statusCode || (5 === (int)($statusCode/100))))
				{
					break;
				}
			}
			if($this->request->getAttribute('customLocation', false) && (301 === $statusCode || 302 === $statusCode) && ++$count <= $this->request->getAttribute('maxRedirects', 10))
			{
				// 自己实现重定向
				$url = $this->result->getHeaderLine('location');
			}
			else
			{
				break;
			}
		}while(true);
		// 关闭保存至文件的句柄
		if(null !== $this->saveFileFp)
		{
			fclose($this->saveFileFp);
			$this->saveFileFp = null;
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
		$rawHeaders = explode("\r\n\r\n", trim($headerContent), 2);
		$requestCount = count($rawHeaders);
		for($i = 0; $i < $requestCount; ++$i)
		{
			$allHeaders[] = $this->parseHeaderOneRequest($rawHeaders[$i]);
		}
		if($requestCount > 0)
		{
			$headers = $allHeaders[$requestCount - 1];
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
			$list = explode(';', $matches[1][$i]);
			$count2 = count($list);
			if(isset($list[0]))
			{
				list($cookieName, $value) = explode('=', $list[0], 2);
				$cookieName = trim($cookieName);
				$cookies[$cookieName] = array('value'=>$value);
				for($j = 1; $j < $count2; ++$j)
				{
					$kv = explode('=', $list[$j], 2);
					$cookies[$cookieName][trim($kv[0])] = isset($kv[1]) ? $kv[1] : true;
				}
			}
		}
		$this->result = $this->result->withCookieOriginParams($cookies)
									->withError(curl_error($this->handler))
									->withErrno(curl_errno($this->handler));

        curl_close($this->handler);
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
				CURLOPT_SSL_VERIFYPEER	=> true,
				CURLOPT_CAINFO			=> $this->request->getAttribute('caCert'),
				CURLOPT_SSL_VERIFYHOST	=> 2,
			));
		}
		else
		{
			curl_setopt_array($this->handler, array(
				CURLOPT_SSL_VERIFYPEER	=> false,
				CURLOPT_SSL_VERIFYHOST	=> 0,
			));
		}
		$certPath = $this->request->getAttribute('certPath', '');
		if('' !== $certPath)
		{
			curl_setopt_array($this->handler, array(
				CURLOPT_SSLCERT			=> $certPath,
				CURLOPT_SSLCERTPASSWD	=> $this->request->getAttribute('certPassword'),
				CURLOPT_SSLCERTTYPE		=> $this->request->getAttribute('certType', 'pem'),
			));
		}
		$keyPath = $this->request->getAttribute('keyPath', '');
		if('' !== $keyPath)
		{
			curl_setopt_array($this->handler, array(
				CURLOPT_SSLKEY			=> $keyPath,
				CURLOPT_SSLKEYPASSWD	=> $this->request->getAttribute('keyPassword'),
				CURLOPT_SSLKEYTYPE		=> $this->request->getAttribute('keyType', 'pem'),
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
			curl_setopt_array($this->handler, array(
				CURLOPT_HEADER => false,
				CURLOPT_RETURNTRANSFER => false,
			));
			$last = substr($saveFilePath, -1, 1);
			if('/' === $last || '\\' === $last)
			{
				// 自动获取文件名
				$saveFilePath .= basename($this->url);
			}
			$this->saveFileFp = fopen($saveFilePath, $this->request->getAttribute('saveFileMode', 'w+'));
			curl_setopt($this->handler, CURLOPT_FILE, $this->saveFileFp);
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
				CURLOPT_PROXYAUTH	=> self::$proxyAuths[$this->request->getAttribute('proxy.auth', 'basic')],
				CURLOPT_PROXY		=> $this->request->getAttribute('proxy.server'),
				CURLOPT_PROXYPORT	=> $this->request->getAttribute('proxy.port'),
				CURLOPT_PROXYUSERPWD=> $this->request->getAttribute('proxy.username', '') . ':' . $this->request->getAttribute('proxy.password', ''),
				CURLOPT_PROXYTYPE	=> 'socks5' === $type ? (defined('CURLPROXY_SOCKS5_HOSTNAME') ? CURLPROXY_SOCKS5_HOSTNAME : self::$proxyType[$type]) : self::$proxyType[$type],
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
		$content = '';
		foreach($this->request->getCookieParams() as $name => $value)
		{
			$content .= "{$name}={$value}; ";
		}
		curl_setopt($this->handler, CURLOPT_COOKIE, $content);
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
			CURLOPT_CONNECTTIMEOUT_MS		=> $this->request->getAttribute('connectTimeout', 30000),
			// 总超时
			CURLOPT_TIMEOUT_MS				=> $this->request->getAttribute('timeout', 0),
			// 下载限速
			CURLOPT_MAX_RECV_SPEED_LARGE	=> $this->request->getAttribute('downloadSpeed'),
			// 上传限速
			CURLOPT_MAX_SEND_SPEED_LARGE	=> $this->request->getAttribute('uploadSpeed'),
			// 连接中用到的用户名和密码
			CURLOPT_USERPWD					=> $userPwd,
		));
	}
}