<?php
namespace Yurun\Until;

class HttpRequest
{
	/**
	 * CURL操作对象
	 * @var resource
	 */
	public $handler;

	/**
	 * Url地址
	 * @var string
	 */
	public $url;

	/**
	 * 发送内容
	 * @var mixed
	 */
	public $content;

	/**
	 * CurlOptions
	 * @var array
	 */
	public $options = array();

	/**
	 * header头
	 * @var array
	 */
	public $headers = array();

	/**
	 * Cookies
	 * @var array
	 */
	public $cookies = array();

	/**
	 * 保存Cookie文件的文件名
	 * @var string
	 */
	public $cookieFileName = '';

	/**
	 * 失败重试次数
	 * @var int
	 */
	public $retry = 0;

	/**
	 * 是否使用代理
	 * @var bool
	 */
	public $useProxy = false;

	/**
	 * 代理设置
	 * @var array
	 */
	public $proxy = array();

	/**
	 * 是否验证证书
	 * @var bool
	 */
	public $IsVerifyCA = false;

	/**
	 * CA根证书路径
	 * @var string
	 */
	public $caCert;

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
	 * __construct
	 * @return mixed 
	 */
	public function __construct()
	{
		$this->open();
		$this->cookieFileName = tempnam(sys_get_temp_dir(),'');
	}

	public function __destruct()
	{
		$this->close();
	}

	public function open()
	{
		$this->handler = curl_init();
		$this->retry = 0;
		$this->headers = $this->options = array();
		$this->url = $this->content = '';
		$this->useProxy = false;
		$this->proxy = array(
			'auth'	=>	'basic',
			'type'	=>	'http',
		);
	}

	public function close()
	{
		if(null !== $this->handler)
		{
			curl_close($this->handler);
			$this->handler = null;
		}
	}

	/**
	 * 创建一个新会话
	 * @return HttpRequest 
	 */
	public static function newSession()
	{
		return new static;
	}

	/**
	 * 设置Url
	 * @param mixed $url 
	 * @return HttpRequest 
	 */
	public function url($url)
	{
		$this->url = $url;
		return $this;
	}

	/**
	 * 设置发送内容
	 * @param mixed $content 
	 * @return HttpRequest 
	 */
	public function content($content)
	{
		$this->content = $content;
		return $this;
	}

	/**
	 * 设置参数
	 * @param mixed $content 
	 * @return HttpRequest 
	 */
	public function params($params)
	{
		$this->content = $params;
		return $this;
	}

	/**
	 * 批量设置CURL的Option
	 * @param array $options 
	 * @return HttpRequest 
	 */
	public function options($options)
	{
		foreach($options as $key => $value)
		{
			$this->options[$key] = $value;
		}
		return $this;
	}

	/**
	 * 设置CURL的Option
	 * @param int $option 
	 * @param mixed $value 
	 * @return HttpRequest 
	 */
	public function option($option, $value)
	{
		$this->options[$option] = $value;
		return $this;
	}

	/**
	 * 批量设置CURL的Option
	 * @param array $options 
	 * @return HttpRequest 
	 */
	public function headers($headers)
	{
		$this->headers = array_merge($this->headers, $headers);
		return $this;
	}

	/**
	 * 设置CURL的Option
	 * @param int $option 
	 * @param mixed $value 
	 * @return HttpRequest 
	 */
	public function header($header, $value)
	{
		$this->headers[$header] = $value;
		return $this;
	}

	/**
	 * 设置Accept
	 * @param string $accept 
	 * @return HttpRequest 
	 */
	public function accept($accept)
	{
		$this->headers['Accept'] = $accept;
		return $this;
	}

	/**
	 * 设置Accept-Language
	 * @param string $acceptLanguage
	 * @return HttpRequest 
	 */
	public function acceptLanguage($acceptLanguage)
	{
		$this->headers['Accept-Language'] = $acceptLanguage;
		return $this;
	}

	/**
	 * 设置Accept-Encoding
	 * @param string $acceptEncoding 
	 * @return HttpRequest 
	 */
	public function acceptEncoding($acceptEncoding)
	{
		$this->headers['Accept-Encoding'] = $acceptEncoding;
		return $this;
	}

	/**
	 * 设置Accept-Ranges
	 * @param string $acceptRanges 
	 * @return HttpRequest 
	 */
	public function acceptRanges($acceptRanges)
	{
		$this->headers['Accept-Ranges'] = $acceptRanges;
		return $this;
	}

	/**
	 * 设置Cache-Control
	 * @param string $cacheControl 
	 * @return HttpRequest 
	 */
	public function cacheControl($cacheControl)
	{
		$this->headers['Cache-Control'] = $cacheControl;
		return $this;
	}

	/**
	 * 设置Cookies
	 * @param array $headers 
	 * @return HttpRequest 
	 */
	public function cookies($headers)
	{
		$this->cookies = array_merge($this->cookies, $headers);
		return $this;
	}

	/**
	 * 设置Cookie
	 * @param string $name 
	 * @param string $value 
	 * @return HttpRequest 
	 */
	public function cookie($name, $value)
	{
		$this->cookies[$name] = $value;
		return $this;
	}

	/**
	 * 设置Content-Type
	 * @param string $contentType 
	 * @return HttpRequest 
	 */
	public function contentType($contentType)
	{
		$this->headers['Content-Type'] = $contentType;
		return $this;
	}

	/**
	 * 设置Range
	 * @param string $range 
	 * @return HttpRequest 
	 */
	public function range($range)
	{
		$this->headers['Range'] = $range;
		return $this;
	}

	/**
	 * 设置Referer
	 * @param string $referer 
	 * @return HttpRequest 
	 */
	public function referer($referer)
	{
		$this->headers['Referer'] = $referer;
		return $this;
	}

	/**
	 * 设置User-Agent
	 * @param string $userAgent 
	 * @return HttpRequest 
	 */
	public function userAgent($userAgent)
	{
		$this->headers['User-Agent'] = $userAgent;
		return $this;
	}

	/**
	 * 设置User-Agent，userAgent的别名
	 * @param string $userAgent 
	 * @return HttpRequest 
	 */
	public function ua($userAgent)
	{
		return $this->userAgent($userAgent);
	}

	/**
	 * 设置失败重试次数，状态码非200时重试
	 * @param string $userAgent 
	 * @return HttpRequest 
	 */
	public function retry($retry)
	{
		$this->retry = $retry;
		return $this;
	}

	/**
	 * 代理
	 * @param string $server 
	 * @param int $port 
	 * @param string $type 
	 * @param string $auth 
	 * @return HttpRequest 
	 */
	public function proxy($server, $port, $type = 'http', $auth = 'basic')
	{
		$this->useProxy = true;
		$this->proxy = array(
			'server'	=>	$server,
			'port'		=>	$port,
			'type'		=>	$type,
			'auth'		=>	$auth,
		);
		return $this;
	}

	/**
	 * 发送请求
	 * @param string $url 
	 * @param array $params 
	 * @return HttpResponse 
	 */
	public function send($url, $params = array(), $method = 'GET')
	{
		if(!empty($params))
		{
			if(is_array($params))
			{
				$this->content = http_build_query($params);
			}
			else if($params instanceof HttpRequestMultipartBody)
			{
				$this->content = $params->content();
				$this->contentType(sprintf('multipart/form-data; boundary=%s', $params->getBoundary()));
			}
			else
			{
				$this->content = $params;
			}
		}
		curl_setopt_array($this->handler, array(
			// 请求地址
			CURLOPT_URL				=> $url,
			// 请求方法
			CURLOPT_CUSTOMREQUEST	=> $method,
			// 返回内容
			CURLOPT_RETURNTRANSFER	=> true,
			// 返回header
			CURLOPT_HEADER			=> true,
			// 发送内容
			CURLOPT_POSTFIELDS		=> $this->content,
			// 保存cookie
			CURLOPT_COOKIEFILE		=> $this->cookieFileName,
			CURLOPT_COOKIEJAR		=> $this->cookieFileName,
			// 自动重定向
			CURLOPT_FOLLOWLOCATION	=> true,
		));
		$this->parseCA();
		$this->parseOptions();
		$this->parseProxy();
		$this->parseHeaders();
		$this->parseCookies();
		for($i = 0; $i <= $this->retry; ++$i)
		{
			$response = new HttpResponse($this->handler, curl_exec($this->handler));
			if(0 >= $this->retry || 200 === $response->httpCode())
			{
				break;
			}
		}
		return $response;
	}

	/**
	 * GET请求
	 * @param string $url 
	 * @param array $params 
	 * @return HttpResponse 
	 */
	public function get($url, $params = array())
	{
		return $this->send($url, $params, 'GET');
	}

	/**
	 * POST请求
	 * @param string $url 
	 * @param array $params 
	 * @return HttpResponse 
	 */
	public function post($url, $params = array())
	{
		return $this->send($url, $params, 'POST');
	}

	/**
	 * HEAD请求
	 * @param string $url 
	 * @param array $params 
	 * @return HttpResponse 
	 */
	public function head($url, $params = array())
	{
		return $this->send($url, $params, 'HEAD');
	}

	/**
	 * PUT请求
	 * @param string $url 
	 * @param array $params 
	 * @return HttpResponse 
	 */
	public function put($url, $params = array())
	{
		return $this->send($url, $params, 'PUT');
	}

	/**
	 * PATCH请求
	 * @param string $url 
	 * @param array $params 
	 * @return HttpResponse 
	 */
	public function patch($url, $params = array())
	{
		return $this->send($url, $params, 'PATCH');
	}

	/**
	 * DELETE请求
	 * @param string $url 
	 * @param array $params 
	 * @return HttpResponse 
	 */
	public function delete($url, $params = array())
	{
		return $this->send($url, $params, 'DELETE');
	}

	/**
	 * 处理Options
	 */
	protected function parseOptions()
	{
		curl_setopt_array($this->handler, $this->options);
	}

	/**
	 * 处理代理
	 */
	protected function parseProxy()
	{
		if($this->useProxy)
		{
			curl_setopt_array($this->handler, array(
				CURLOPT_PROXYAUTH	=> self::$proxyAuths[$this->proxy['auth']],
				CURLOPT_PROXY		=> $this->proxy['server'],
				CURLOPT_PROXYPORT	=> $this->proxy['port'],
				CURLOPT_PROXYTYPE	=> 'socks5' === $this->proxy['type'] ? (defined('CURLPROXY_SOCKS5_HOSTNAME') ? CURLPROXY_SOCKS5_HOSTNAME : self::$proxyType[$this->proxy['type']]) : self::$proxyType[$this->proxy['type']],
			));
		}
	}

	/**
	 * 处理Headers
	 */
	protected function parseHeaders()
	{
		curl_setopt($this->handler, CURLOPT_HTTPHEADER, $this->parseHeadersFormat());
	}

	/**
	 * 处理Cookie
	 */
	protected function parseCookies()
	{
		$content = '';
		foreach($this->cookies as $name => $value)
		{
			$content .= "{$name}={$value}; ";
		}
		curl_setopt($this->handler, CURLOPT_COOKIE, $content);
	}

	/**
	 * 处理成CURL可以识别的headers格式
	 * @return array 
	 */
	protected function parseHeadersFormat()
	{
		$headers = array();
		foreach($this->headers as $name => $value)
		{
			$headers[] = $name . ':' . $value;
		}
		return $headers;
	}
	
	/**
	 * 处理证书
	 */
	protected function parseCA()
	{
		if($this->IsVerifyCA)
		{
			curl_setopt_array($this->handler, array(
				CURLOPT_SSL_VERIFYPEER	=> true,
				CURLOPT_CAINFO			=> $this->caCert,
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
	}
}