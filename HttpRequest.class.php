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
	 * __construct
	 * @return mixed 
	 */
	public function __construct()
	{
		$this->newSession();
	}

	public function __destruct()
	{
		$this->close();
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
	public function newSession()
	{
		$this->handler = curl_init();
		return $this;
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
	 * 发送请求
	 * @param string $url 
	 * @param array $params 
	 * @return HttpRequest 
	 */
	public function send($url, $params = array(), $method = 'GET')
	{
		curl_setopt($this->handler, CURLOPT_URL, $url);
		return new HttpResponse($this->handler);
	}

	/**
	 * GET请求
	 * @param string $url 
	 * @param array $params 
	 * @return HttpRequest 
	 */
	public function get($url, $params = array())
	{
		return $this->send($url, $params, 'GET');
	}

	/**
	 * POST请求
	 * @param string $url 
	 * @param array $params 
	 * @return HttpRequest 
	 */
	public function post($url, $params = array())
	{
		return $this->send($url, $params, 'POST');
	}

	/**
	 * HEAD请求
	 * @param string $url 
	 * @param array $params 
	 * @return HttpRequest 
	 */
	public function head($url, $params = array())
	{
		return $this->send($url, $params, 'HEAD');
	}

	/**
	 * PUT请求
	 * @param string $url 
	 * @param array $params 
	 * @return HttpRequest 
	 */
	public function put($url, $params = array())
	{
		return $this->send($url, $params, 'PUT');
	}

	/**
	 * PATCH请求
	 * @param string $url 
	 * @param array $params 
	 * @return HttpRequest 
	 */
	public function patch($url, $params = array())
	{
		return $this->send($url, $params, 'PATCH');
	}

	/**
	 * DELETE请求
	 * @param string $url 
	 * @param array $params 
	 * @return HttpRequest 
	 */
	public function delete($url, $params = array())
	{
		return $this->send($url, $params, 'DELETE');
	}
}