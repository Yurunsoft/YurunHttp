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
	 * __construct
	 * @return mixed 
	 */
	public function __construct($handler)
	{
		$this->handler = $handler;
	}
}