<?php
namespace Yurun\Until;

class HttpResponse
{
	/**
	 * CURL操作对象
	 * @var mixed
	 */
	public $handler;

	/**
	 * 请求返回结果
	 * @var mixed
	 */
	public $response;

	/**
	 * 返回头
	 * @var array
	 */
	public $headers = array();

	/**
	 * Cookie
	 * @var array
	 */
	public $cookies = array();

	/**
	 * 头部内容
	 * @var mixed
	 */
	public $headerContent = '';

	/**
	 * 返回结果
	 * @var string
	 */
	public $body = '';

	/**
	 * __construct
	 * @return mixed 
	 */
	public function __construct($handler, $response)
	{
		$this->handler = $handler;
		$this->response = $response;
		$this->parseResponse();
	}

	/**
	 * 获取http状态码
	 * @return int 
	 */
	public function httpCode()
	{
		return curl_getinfo($this->handler, CURLINFO_HTTP_CODE);
	}

	/**
	 * 处理
	 * @return mixed 
	 */
	protected function parseResponse()
	{
		// 分离header和body
		$headerSize = curl_getinfo($this->handler, CURLINFO_HEADER_SIZE);
		$this->headerContent = substr($this->response, 0, $headerSize);
		$this->body = substr($this->response, $headerSize);
		$this->parseHeader();
		$this->parseCookie();
	}

	/**
	 * 处理header
	 */
	protected function parseHeader()
	{
		preg_match_all('/([^:\r\n]+)\s*:\s*([^\r\n]+)\r\n/', $this->headerContent, $matches, PREG_SET_ORDER);
		foreach($matches as $match)
		{
			$this->headers[$match[1]] = $match[2];
		}
	}

	/**
	 * 处理cookie
	 */
	protected function parseCookie()
	{
		$count = preg_match_all('/set-cookie\s*:\s*([^\r\n]+)/i', $this->headerContent, $matches);
		for($i = 0; $i < $count; ++$i)
		{
			$list = explode(';', $matches[1][$i]);
			$count2 = count($list);
			if(isset($list[0]))
			{
				list($cookieName, $value) = explode('=', $list[0]);
				$cookieName = trim($cookieName);
				$this->cookies[$cookieName] = array('value'=>$value);
				for($j = 1; $j < $count2; ++$j)
				{
					list($name, $value) = explode('=', $list[$j]);
					$this->cookies[$cookieName][trim($name)] = $value;
				}
			}
		}
	}
}