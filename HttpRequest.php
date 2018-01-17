<?php
namespace Yurun\Until;

class HttpRequest
{
	/**
	 * CURL操作对象，`curl_init()`的返回值
	 * @var resource
	 */
	public $handler;

	/**
	 * 需要请求的Url地址
	 * @var string
	 */
	public $url;

	/**
	 * 发送内容，可以是字符串、数组、`HttpRequestMultipartBody`
	 * @var mixed
	 */
	public $content;

	/**
	 * `curl_setopt_array()`所需要的第二个参数
	 * @var array
	 */
	public $options = array();

	/**
	 * 请求头
	 * @var array
	 */
	public $headers = array();

	/**
	 * Cookies
	 * @var array
	 */
	public $cookies = array();

	/**
	 * 保存Cookie文件的文件名，为空不保存
	 * @var string
	 */
	public $cookieFileName = '';

	/**
	 * 失败重试次数，默认为0
	 * @var int
	 */
	public $retry = 0;

	/**
	 * 是否使用代理，默认false
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
	public $isVerifyCA = false;

	/**
	 * CA根证书路径
	 * @var string
	 */
	public $caCert;

	/**
	 * 连接超时时间，单位：毫秒
	 * @var int
	 */
	public $connectTimeout = 30000;

	/**
	 * 总超时时间，单位：毫秒
	 * @var int
	 */
	public $timeout = 0;

	/**
	 * 下载限速，为0则不限制，单位：字节
	 * @var int
	 */
	public $downloadSpeed;

	/**
	 * 上传限速，为0则不限制，单位：字节
	 * @var int
	 */
	public $uploadSpeed;

	/**
	 * 用于连接中需要的用户名
	 * @var string
	 */
	public $username;

	/**
	 * 用于连接中需要的密码
	 * @var string
	 */
	public $password;

	/**
	 * 请求结果保存至文件的配置
	 * @var mixed
	 */
	public $saveFileOption = array();

	/**
	 * 根据location自动重定向
	 * @var bool
	 */
	public $followLocation = true;

	/**
	 * 最大重定向次数
	 * @var int
	 */
	public $maxRedirects = 10;

	/**
	 * 证书类型
	 * 支持的格式有"PEM" (默认值), "DER"和"ENG"
	 * @var string
	 */
	public $certType = 'pem';

	/**
	 * 一个包含 PEM 格式证书的文件名
	 * @var string
	 */
	
	public $certPath = '';
	/**
	 * 使用证书需要的密码
	 * @var string
	 */
	public $certPassword = null;

	/**
	 * certType规定的私钥的加密类型，支持的密钥类型为"PEM"(默认值)、"DER"和"ENG"
	 * @var string
	 */
	public $keyType = 'pem';
	
	/**
	 * 包含 SSL 私钥的文件名
	 * @var string
	 */
	public $keyPath = '';

	/**
	 * SSL私钥的密码
	 * @var string
	 */
	public $keyPassword = null;

	/**
	 * 使用自定义实现的重定向，性能较差。如果不是环境不支持自动重定向，请勿设为true
	 * @var bool
	 */
	public static $customLocation = false;

	/**
	 * 临时目录，有些特殊环境（如某国内虚拟主机）需要特别设置一下临时文件目录
	 * @var string
	 */
	public static $tempDir;

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
	 * 构造方法
	 * @return mixed 
	 */
	public function __construct()
	{
		$this->open();
		$this->cookieFileName = tempnam(null === self::$tempDir ? sys_get_temp_dir() : self::$tempDir,'');
	}

	/**
	 * 析构方法
	 */
	public function __destruct()
	{
		$this->close();
	}

	/**
	 * 打开一个新连接，初始化所有参数。一般不需要手动调用。
	 * @return void
	 */
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
		$this->isVerifyCA = false;
		$this->caCert = null;
		$this->connectTimeout = 30000;
		$this->timeout = 0;
		$this->downloadSpeed = null;
		$this->uploadSpeed = null;
		$this->username = null;
		$this->password = null;
		$this->saveFileOption = array();
	}

	/**
	 * 关闭连接。一般不需要手动调用。
	 * @return void
	 */
	public function close()
	{
		if(null !== $this->handler)
		{
			curl_close($this->handler);
			$this->handler = null;
			if(is_file($this->cookieFileName))
			{
				unlink($this->cookieFileName);
			}
		}
	}

	/**
	 * 创建一个新会话，等同于new
	 * @return HttpRequest 
	 */
	public static function newSession()
	{
		return new static;
	}

	/**
	 * 设置请求地址
	 * @param string $url 请求地址
	 * @return HttpRequest 
	 */
	public function url($url)
	{
		$this->url = $url;
		return $this;
	}

	/**
	 * 设置发送内容，requestBody的别名
	 * @param mixed $content 发送内容，可以是字符串、数组、HttpRequestMultipartBody
	 * @return HttpRequest 
	 */
	public function content($content)
	{
		return $this->requestBody($content);
	}

	/**
	 * 设置参数，requestBody的别名
	 * @param mixed $params 发送内容，可以是字符串、数组、HttpRequestMultipartBody
	 * @return HttpRequest 
	 */
	public function params($params)
	{
		return $this->requestBody($params);
	}

	/**
	 * 设置请求主体
	 * @param mixed $requestBody 发送内容，可以是字符串、数组、HttpRequestMultipartBody
	 * @return HttpRequest 
	 */
	public function requestBody($requestBody)
	{
		$this->content = $requestBody;
		return $this;
	}

	/**
	 * 批量设置CURL的Option
	 * @param array $options curl_setopt_array()所需要的第二个参数
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
	 * @param int $option 需要设置的CURLOPT_XXX选项
	 * @param mixed $value 值
	 * @return HttpRequest 
	 */
	public function option($option, $value)
	{
		$this->options[$option] = $value;
		return $this;
	}

	/**
	 * 批量设置请求头
	 * @param array $headers 
	 * @return HttpRequest 
	 */
	public function headers($headers)
	{
		$this->headers = array_merge($this->headers, $headers);
		return $this;
	}

	/**
	 * 设置请求头
	 * @param string $header 请求头名称
	 * @param string $value 值
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
	 * 批量设置Cookies
	 * @param array $cookies 键值对应数组
	 * @return HttpRequest 
	 */
	public function cookies($cookies)
	{
		$this->cookies = array_merge($this->cookies, $cookies);
		return $this;
	}

	/**
	 * 设置Cookie
	 * @param string $name 名称
	 * @param string $value 值
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
	 * @param string $retry 
	 * @return HttpRequest 
	 */
	public function retry($retry)
	{
		$this->retry = $retry < 0 ? 0 : $retry;   //至少请求1次，即重试0次
		return $this;
	}

	/**
	 * 代理
	 * @param string $server 代理服务器地址
	 * @param int $port 代理服务器端口
	 * @param string $type 代理类型，支持：http、socks4、socks4a、socks5
	 * @param string $auth 代理认证方式，支持：basic、ntlm。一般默认basic
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
	 * 设置超时时间
	 * @param int $timeout 总超时时间，单位：毫秒
	 * @param int $connectTimeout 连接超时时间，单位：毫秒
	 * @return HttpRequest 
	 */
	public function timeout($timeout = null, $connectTimeout = null)
	{
		if(null !== $timeout)
		{
			$this->timeout = $timeout;
		}
		if(null !== $connectTimeout)
		{
			$this->connectTimeout = $connectTimeout;
		}
		return $this;
	}

	/**
	 * 限速
	 * @param int $download 下载速度，为0则不限制，单位：字节
	 * @param int $upload 上传速度，为0则不限制，单位：字节
	 * @return HttpRequest 
	 */
	public function limitRate($download = 0, $upload = 0)
	{
		$this->downloadSpeed = $download;
		$this->uploadSpeed = $upload;
		return $this;
	}

	/**
	 * 设置用于连接中需要的用户名和密码
	 * @param string $username 用户名
	 * @param string $password 密码
	 * @return HttpRequest 
	 */
	public function userPwd($username, $password)
	{
		$this->username = $username;
		$this->password = $password;
		return $this;
	}

	/**
	 * 保存至文件的设置
	 * @param string $filePath 文件路径
	 * @param string $fileMode 文件打开方式，默认w+
	 * @return HttpRequest 
	 */
	public function saveFile($filePath, $fileMode = 'w+')
	{
		$this->saveFileOption['filePath'] = $filePath;
		$this->saveFileOption['fileMode'] = $fileMode;
		return $this;
	}

	/**
	 * 获取文件保存路径
	 * @return string 
	 */
	public function getSavePath()
	{
		return $this->saveFileOption['savePath'];
	}

	/**
	 * 设置SSL证书
	 * @param string $path 一个包含 PEM 格式证书的文件名
	 * @param string $type 证书类型，支持的格式有”PEM”(默认值),“DER”和”ENG”
	 * @param string $password 使用证书需要的密码
	 * @return HttpRequest
	 */
	public function sslCert($path, $type = null, $password = null)
	{
		$this->certPath = $path;
		if(null !== $type)
		{
			$this->certType = $type;
		}
		if(null !== $password)
		{
			$this->certPassword = $password;
		}
		return $this;
	}

	/**
	 * 设置SSL私钥
	 * @param string $path 包含 SSL 私钥的文件名
	 * @param string $type certType规定的私钥的加密类型，支持的密钥类型为”PEM”(默认值)、”DER”和”ENG”
	 * @param string $password SSL私钥的密码
	 * @return HttpRequest
	 */
	public function sslKey($path, $type = null, $password = null)
	{
		$this->keyPath = $path;
		if(null !== $type)
		{
			$this->keyType = $type;
		}
		if(null !== $password)
		{
			$this->keyPassword = $password;
		}
		return $this;
	}

	/**
	 * 发送请求，所有请求的老祖宗
	 * @param string $url 请求地址，如果为null则取url属性值
	 * @param array $requestBody 发送内容，可以是字符串、数组、`HttpRequestMultipartBody`，如果为空则取content属性值
	 * @param array $method 请求方法，GET、POST等
	 * @return HttpResponse 
	 */
	public function send($url = null, $requestBody = array(), $method = 'GET')
	{
		if(null !== $url)
		{
			$this->url = $url;
		}
		if(!empty($requestBody))
		{
			if(is_array($requestBody))
			{
				$this->content = http_build_query($requestBody, '', '&');
			}
			else if($requestBody instanceof HttpRequestMultipartBody)
			{
				$this->content = $requestBody->content();
				$this->contentType(sprintf('multipart/form-data; boundary=%s', $requestBody->getBoundary()));
			}
			else
			{
				$this->content = $requestBody;
			}
		}
		$options = array(
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
			CURLOPT_FOLLOWLOCATION	=> self::$customLocation ? false : $this->followLocation,
			// 最大重定向次数
			CURLOPT_MAXREDIRS		=> $this->maxRedirects,
		);
		// 自动解压缩支持
		if(isset($this->headers['Accept-Encoding']))
		{
			$options[CURLOPT_ENCODING] = $this->headers['Accept-Encoding'];
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
		do{
			curl_setopt($this->handler, CURLOPT_URL, $url);
			for($i = 0; $i <= $this->retry; ++$i)
			{
				$response = new HttpResponse($this->handler, curl_exec($this->handler));
				$httpCode = $response->httpCode();
				// 状态码为5XX或者0才需要重试
				if(!(0 === $httpCode || (5 === (int)($httpCode/100))))
				{
					break;
				}
			}
			if(self::$customLocation && (301 === $httpCode || 302 === $httpCode) && ++$count <= $this->maxRedirects)
			{
				$url = $response->headers['Location'];
			}
			else
			{
				break;
			}
		}while(true);
		// 关闭保存至文件的句柄
		if(isset($this->saveFileOption['fp']))
		{
			fclose($this->saveFileOption['fp']);
			$this->saveFileOption['fp'] = null;
		}
		return $response;
	}

	/**
	 * GET请求
	 * @param string $url 请求地址，如果为null则取url属性值
	 * @param array $requestBody 发送内容，可以是字符串、数组、`HttpRequestMultipartBody`，如果为空则取content属性值
	 * @return HttpResponse 
	 */
	public function get($url = null, $requestBody = array())
	{
		if(!empty($requestBody))
		{
			if(strpos($url, '?'))
			{
				$url .= '&';
			}
			else
			{
				$url .= '?';
			}
			$url .= http_build_query($requestBody, '', '&');
		}
		return $this->send($url, array(), 'GET');
	}

	/**
	 * POST请求
	 * @param string $url 请求地址，如果为null则取url属性值
	 * @param array $requestBody 发送内容，可以是字符串、数组、`HttpRequestMultipartBody`，如果为空则取content属性值
	 * @return HttpResponse 
	 */
	public function post($url = null, $requestBody = array())
	{
		return $this->send($url, $requestBody, 'POST');
	}

	/**
	 * HEAD请求
	 * @param string $url 请求地址，如果为null则取url属性值
	 * @param array $requestBody 发送内容，可以是字符串、数组、`HttpRequestMultipartBody`，如果为空则取content属性值
	 * @return HttpResponse 
	 */
	public function head($url = null, $requestBody = array())
	{
		return $this->send($url, $requestBody, 'HEAD');
	}

	/**
	 * PUT请求
	 * @param string $url 请求地址，如果为null则取url属性值
	 * @param array $requestBody 发送内容，可以是字符串、数组、`HttpRequestMultipartBody`，如果为空则取content属性值
	 * @return HttpResponse 
	 */
	public function put($url = null, $requestBody = array())
	{
		return $this->send($url, $requestBody, 'PUT');
	}

	/**
	 * PATCH请求
	 * @param string $url 请求地址，如果为null则取url属性值
	 * @param array $requestBody 发送内容，可以是字符串、数组、`HttpRequestMultipartBody`，如果为空则取content属性值
	 * @return HttpResponse 
	 */
	public function patch($url = null, $requestBody = array())
	{
		return $this->send($url, $requestBody, 'PATCH');
	}

	/**
	 * DELETE请求
	 * @param string $url 请求地址，如果为null则取url属性值
	 * @param array $requestBody 发送内容，可以是字符串、数组、`HttpRequestMultipartBody`，如果为空则取content属性值
	 * @return HttpResponse 
	 */
	public function delete($url = null, $requestBody = array())
	{
		return $this->send($url, $requestBody, 'DELETE');
	}

	/**
	 * 直接下载文件
	 * @param string $fileName 保存路径
	 * @param string $url 下载文件地址
	 * @param array $requestBody 发送内容，可以是字符串、数组、`HttpRequestMultipartBody`，如果为空则取content属性值
	 * @param string $method 请求方法，GET、POST等，一般用GET
	 * @return HttpResponse
	 */
	public function download($fileName, $url = null, $requestBody = array(), $method = 'GET')
	{
		$result = $this->saveFile($fileName)->send($url, $requestBody, $method);
		$this->saveFileOption = array();
		return $result;
	}

	/**
	 * 处理Options
	 * @return void
	 */
	protected function parseOptions()
	{
		curl_setopt_array($this->handler, $this->options);
		// 请求结果保存为文件
		if(isset($this->saveFileOption['filePath']) && null !== $this->saveFileOption['filePath'])
		{
			curl_setopt_array($this->handler, array(
				CURLOPT_HEADER => false,
				CURLOPT_RETURNTRANSFER => false,
			));
			$filePath = $this->saveFileOption['filePath'];
			$last = substr($filePath, -1, 1);
			if('/' === $last || '\\' === $last)
			{
				// 自动获取文件名
				$filePath .= basename($this->url);
			}
			$this->saveFileOption['savePath'] = $filePath;
			$this->saveFileOption['fp'] = fopen($filePath, isset($this->saveFileOption['fileMode']) ? $this->saveFileOption['fileMode'] : 'w+');
			curl_setopt($this->handler, CURLOPT_FILE, $this->saveFileOption['fp']);
		}
	}

	/**
	 * 处理代理
	 * @return void
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
	 * @return void
	 */
	protected function parseHeaders()
	{
		curl_setopt($this->handler, CURLOPT_HTTPHEADER, $this->parseHeadersFormat());
	}

	/**
	 * 处理Cookie
	 * @return void
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
	 * 处理SSL
	 * @return void
	 */
	protected function parseSSL()
	{
		if($this->isVerifyCA)
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
		if('' !== $this->certPath)
		{
			curl_setopt_array($this->handler, array(
				CURLOPT_SSLCERT			=> $this->certPath,
				CURLOPT_SSLCERTPASSWD	=> $this->certPassword,
				CURLOPT_SSLCERTTYPE		=> $this->certType,
			));
		}
		if('' !== $this->keyPath)
		{
			curl_setopt_array($this->handler, array(
				CURLOPT_SSLKEY			=> $this->keyPath,
				CURLOPT_SSLKEYPASSWD	=> $this->keyPassword,
				CURLOPT_SSLKEYTYPE		=> $this->keyType,
			));
		}
	}

	/**
	 * 处理网络相关
	 * @return void
	 */
	protected function parseNetwork()
	{
		// 用户名密码处理
		if('' != $this->username)
		{
			$userPwd = $this->username . ':' . $this->password;
		}
		else
		{
			$userPwd = '';
		}
		curl_setopt_array($this->handler, array(
			// 连接超时
			CURLOPT_CONNECTTIMEOUT_MS		=> $this->connectTimeout,
			// 总超时
			CURLOPT_TIMEOUT_MS				=> $this->timeout,
			// 下载限速
			CURLOPT_MAX_RECV_SPEED_LARGE	=> $this->downloadSpeed,
			// 上传限速
			CURLOPT_MAX_SEND_SPEED_LARGE	=> $this->uploadSpeed,
			// 连接中用到的用户名和密码
			CURLOPT_USERPWD					=> $userPwd,
		));
	}
}