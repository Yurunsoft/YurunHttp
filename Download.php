<?php
namespace Yurun\Until;

class Download
{
	use \Yurun\Until\ClassEvent;

	/**
	 * HttpRequest
	 * @var HttpRequest
	 */
	public $http;

	/**
	 * 下载地址
	 * @var string
	 */
	public $url;

	/**
	 * 参数
	 * @var string
	 */
	public $params;

	/**
	 * 请求方法
	 * @var string
	 */
	public $method;

	/**
	 * HttpResponse
	 *
	 * @var HttpResponse
	 */
	public $response;

	/**
	 * 断点续传是否开启，默认为自动检测。常量BreakContinue::
	 * @var int
	 */
	public $breakContinue = BreakContinue::AUTO;

	/**
	 * 文件大小
	 * @var int
	 */
	public $fileSize;

	/**
	 * 是否开启断点续传
	 * @var bool
	 */
	public $isBreakContinue;

	/**
	 * 每个分块大小，单位：字节，默认为1M
	 * @var int
	 */
	public $blockSize = 1048576;

	public function __construct($url, $params = array(), $method = 'GET')
	{
		$this->url = $url;
		$this->params = $params;
		$this->method = $method;
		$this->http = new HttpRequest();
	}

	/**
	 * 获取文件大小，单位：字节。
	 * @return mixed
	 */
	public function getFileSize()
	{
		$this->response = $this->http->headers(array(
			'Range'	=>	'bytes=0-1',
		))->options(array(
			CURLOPT_NOBODY	=>	true
		))->send($this->url, $this->params, $this->method);
		if(isset($this->response->headers['Content-Range']))
		{
			list(, $length) = explode('/', $this->response->headers['Content-Range']);
			return (int)$length;
		}
		else
		{
			return false;
		}
	}

	public function download($filename)
	{
		$this->fileSize = $this->getFileSize();
		if($this->fileSize)
		{
			$canBreakContinue = isset($this->response->headers['Content-Range']);
			switch($this->breakContinue)
			{
				case BreakContinue::AUTO:
					$this->isBreakContinue = $canBreakContinue;
					break;
				case BreakContinue::ON:
					$this->isBreakContinue = (true === $canBreakContinue);
					break;
				case BreakContinue::OFF:
					$this->isBreakContinue = false;
					break;
			}
		}
		else
		{
			$canBreakContinue = $this->isBreakContinue = false;
		}
		$this->http->options(array(
			CURLOPT_NOBODY	=>	false
		));
		if($this->isBreakContinue)
		{
			$fp = fopen($filename, 'a+');
			if(false === $fp)
			{
				throw new \Exception('打开本地文件失败');
			}
			$begin = filesize($filename);
			if(false === $begin)
			{
				throw new \Exception('获取本地文件大小失败');
			}
			while($begin < $this->fileSize)
			{
				$length = min($this->fileSize - $begin, $this->blockSize);
				$this->response = $this->http->headers(array(
					'Range'	=>	'bytes=' . $begin . '-' . ($begin + $length),
				))->send($this->url, $this->params, $this->method);
				if(false === fwrite($fp, $this->response->body))
				{
					fclose($fp);
					throw new \Exception('文件写入失败');
				}
				$begin += $this->response->headers['Content-Length'];
				$this->trigger('progressChanged', array(
					'length'			=>	$this->fileSize,
					'completeLength'	=>	$begin,
					'percent'			=>	$begin / $this->fileSize,
				));
			}
			fclose($fp);
		}
		else
		{
			$this->http->download($filename, $this->url, $this->params, $this->method);
		}
	}
}