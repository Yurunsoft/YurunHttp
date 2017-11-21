<?php
namespace Yurun\Until;

class HttpResponse
{
	/**
	 * CURL操作对象，`curl_init()`的返回值
	 * @var resource
	 */
	public $handler;

	/**
	 * 请求返回结果，包含返回头和返回主体
	 * @var string
	 */
	public $response;

	/**
	 * 返回头, 最后一次请求的返回头
	 * @var array
	 */
	public $headers = array();

	/**
	 * 返回头, 包含中间所有请求(即包含重定向)的返回头
	 * @var array
	 */
	public $allHeaders = array();

	/**
	 * Cookie
	 * @var array
	 */
	public $cookies = array();

	/**
	 * 头部内容
	 * @var string
	 */
	public $headerContent = '';

	/**
	 * 返回结果
	 * @var string
	 */
	public $body = '';

	/**
	 * 是否请求成功
	 * @var boolean
	 */
	public $success;

	/**
	 * 构造方法
	 */
	public function __construct($handler, $response)
	{
		$this->handler = $handler;
		$this->response = $response;
		$this->success = false !== $response;
		$this->parseResponse();
	}

	/**
	 * 获取返回的主体内容
	 * @param string $fromEncoding 请求返回数据的编码，如果不为空则进行编码转换
	 * @param string $toEncoding 要转换到的编码，默认为UTF-8
	 * @return void
	 */
	public function body($fromEncoding = null, $toEncoding = 'UTF-8')
	{
		if(null === $fromEncoding)
		{
			return $this->body;
		}
		else
		{
			return mb_convert_encoding($this->body, $toEncoding, $fromEncoding);
		}
	}

	/**
	 * 获取xml格式内容
	 * @param boolean $assoc 为true时返回数组，为false时返回对象
	 * @param string $fromEncoding 请求返回数据的编码，如果不为空则进行编码转换
	 * @param string $toEncoding 要转换到的编码，默认为UTF-8
	 * @return mixed
	 */
	public function xml($assoc = false, $fromEncoding = null, $toEncoding = 'UTF-8')
	{
		$xml = simplexml_load_string($this->body($fromEncoding, $toEncoding), null, LIBXML_NOCDATA | LIBXML_COMPACT);
		if($assoc)
		{
			$xml = (array)$xml;
		}
		return $xml;
	}

	/**
	 * 获取json格式内容
	 * @param boolean $assoc 为true时返回数组，为false时返回对象
	 * @param string $fromEncoding 请求返回数据的编码，如果不为空则进行编码转换
	 * @param string $toEncoding 要转换到的编码，默认为UTF-8
	 * @return mixed
	 */
	public function json($assoc = false, $fromEncoding = null, $toEncoding = 'UTF-8')
	{
		return json_decode($this->body($fromEncoding, $toEncoding), $assoc);
	}

	/**
	 * 获取jsonp格式内容
	 * @param boolean $assoc 为true时返回数组，为false时返回对象
	 * @param string $fromEncoding 请求返回数据的编码，如果不为空则进行编码转换
	 * @param string $toEncoding 要转换到的编码，默认为UTF-8
	 * @return mixed
	 */
	public function jsonp($assoc = false, $fromEncoding = null, $toEncoding = 'UTF-8')
	{
		$jsonp = trim($this->body($fromEncoding, $toEncoding));
		if(isset($jsonp[0]) && $jsonp[0] !== '[' && $jsonp[0] !== '{') {
			$begin = strpos($jsonp, '(');
			if(false !== $begin)
			{
				$end = strrpos($jsonp, ')');
				if(false !== $end)
				{
					$jsonp = substr($jsonp, $begin + 1, $end - $begin - 1);
				}
			}
		}
		return json_decode($jsonp, $assoc);
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
	 * 获取请求总耗时，单位：秒
	 * @return int
	 */
	public function totalTime()
	{
		return curl_getinfo($this->handler, CURLINFO_TOTAL_TIME);
	}

	/**
	 * 处理
	 */
	protected function parseResponse()
	{
		// 分离header和body
		$headerSize = curl_getinfo($this->handler, CURLINFO_HEADER_SIZE);
		$this->headerContent = substr($this->response, 0, $headerSize);
		$this->body = substr($this->response, $headerSize);
		// PHP 7.0.0开始substr()的 string 字符串长度与 start 相同时将返回一个空字符串。在之前的版本中，这种情况将返回 FALSE 。
		if(false === $this->body)
		{
			$this->body = '';
		}
		$this->parseHeader();
		$this->parseCookie();
	}

	/**
	 * 处理header
	 */
	protected function parseHeader()
	{
		$rawHeaders = explode("\r\n\r\n", trim($this->headerContent), 2);
		$requestCount = count($rawHeaders);
		for($i=0; $i<$requestCount; ++$i){
			$this->allHeaders[] = $this->parseHeaderOneRequest($rawHeaders[$i]);
		}
		if($requestCount>0) $this->headers = $this->allHeaders[$requestCount-1];
	}

	/**
	 * parseHeaderOneRequest
	 * @param string $piece 
	 * @return array
	 */
	protected function parseHeaderOneRequest($piece){
		$tmpHeaders = array();
		$lines = explode("\r\n", $piece);
		$linesCount = count($lines);
		//从1开始，第0行包含了协议信息和状态信息，排除该行
		for($i=1; $i<$linesCount; ++$i){
			$line = trim($lines[$i]);
			if(empty($line)||strstr($line, ':') == false) continue;
			list($key, $value) = explode(':', $line, 2);
			$key = trim($key);
			$value = trim($value);
			if(isset($tmpHeaders[$key])){
				if(is_array($tmpHeaders[$key])){
					$tmpHeaders[$key][] = $value;
				}else{
					$tmp = $tmpHeaders[$key];
					$tmpHeaders[$key] = array(
						$tmp,
						$value
					);
				}
			}else{
				$tmpHeaders[$key] = $value;
			}
		}
		return $tmpHeaders;
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
				list($cookieName, $value) = explode('=', $list[0], 2);
				$cookieName = trim($cookieName);
				$this->cookies[$cookieName] = array('value'=>$value);
				for($j = 1; $j < $count2; ++$j)
				{
					$kv = explode('=', $list[$j], 2);
					$this->cookies[$cookieName][trim($kv[0])] = isset($kv[1]) ? $kv[1] : true;
				}
			}
		}
	}

	/**
	 * 返回当前会话最后一次错误的字符串
	 * @return string
	 */
	public function error()
	{
		return curl_error($this->handler);
	}

	/**
	 * 返回最后一次的错误代码
	 * @return int
	 */
	public function errno()
	{
		return curl_errno($this->handler);
	}
}
