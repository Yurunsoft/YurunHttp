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
			if(empty($line||strstr($line, ':') == false)) continue;
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
}
