<?php
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
	 * 保存Cookie文件的文件名
	 * @var mixed
	 */
	public $cookieFileName = '';

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
		$this->options = array_merge($this->options, $options);
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
	 * 设置Cookie
	 * @param mixed $cookie 
	 * @return HttpRequest 
	 */
	public function cookie($cookie)
	{
		// TODO:
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
			CURLOPT_COOKIEJAR		=> $this->cookieFileName,
			// 自动重定向
			CURLOPT_FOLLOWLOCATION	=> true,
		));
		$this->parseOptions();
		$this->parseHeaders();
		return new HttpResponse($this->handler, curl_exec($this->handler));
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
		$this->options = array();
	}

	/**
	 * 处理Headers
	 */
	protected function parseHeaders()
	{
		curl_setopt($this->handler, CURLOPT_HTTPHEADER, $this->parseHeadersFormat());
		$this->headers = array();
	}

	/**
	 * 处理成CURL可以识别的headers格式
	 * @return mixed 
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
}