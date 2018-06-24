<?php
namespace Yurun\Util\YurunHttp\Http;

use Yurun\Util\YurunHttp\Http\Psr7\ServerRequest as Psr7Request;

class Request extends Psr7Request
{
	/**
	 * 失败重试次数，默认为0
	 * @var int
	 */
	protected $retry = 0;

	/**
	 * 是否使用代理，默认false
	 * @var bool
	 */
	protected $useProxy = false;

	/**
	 * 代理设置
	 * @var array
	 */
	protected $proxy = array();

	/**
	 * 是否验证证书
	 * @var bool
	 */
	protected $isVerifyCA = false;

	/**
	 * CA根证书路径
	 * @var string
	 */
	protected $caCert;

	/**
	 * 连接超时时间，单位：毫秒
	 * @var int
	 */
	protected $connectTimeout = 30000;

	/**
	 * 总超时时间，单位：毫秒
	 * @var int
	 */
	protected $timeout = 0;

	/**
	 * 下载限速，为0则不限制，单位：字节
	 * @var int
	 */
	protected $downloadSpeed;

	/**
	 * 上传限速，为0则不限制，单位：字节
	 * @var int
	 */
	protected $uploadSpeed;

	/**
	 * 用于连接中需要的用户名
	 * @var string
	 */
	protected $username;

	/**
	 * 用于连接中需要的密码
	 * @var string
	 */
	protected $password;

	/**
	 * 请求结果保存至文件的配置
	 * @var mixed
	 */
	protected $saveFileOption = array();

	/**
	 * 根据location自动重定向
	 * @var bool
	 */
	protected $followLocation = true;

	/**
	 * 最大重定向次数
	 * @var int
	 */
	protected $maxRedirects = 10;

	/**
	 * 证书类型
	 * 支持的格式有"PEM" (默认值), "DER"和"ENG"
	 * @var string
	 */
	protected $certType = 'pem';

	/**
	 * 一个包含 PEM 格式证书的文件名
	 * @var string
	 */
	
	protected $certPath = '';
	/**
	 * 使用证书需要的密码
	 * @var string
	 */
	protected $certPassword = null;

	/**
	 * certType规定的私钥的加密类型，支持的密钥类型为"PEM"(默认值)、"DER"和"ENG"
	 * @var string
	 */
	protected $keyType = 'pem';
	
	/**
	 * 包含 SSL 私钥的文件名
	 * @var string
	 */
	protected $keyPath = '';

	/**
	 * SSL私钥的密码
	 * @var string
	 */
	protected $keyPassword = null;

	/**
	 * 使用自定义实现的重定向，性能较差。如果不是环境不支持自动重定向，请勿设为true
	 * @var bool
	 */
	protected static $customLocation = false;

	/**
	 * Get 失败重试次数，默认为0
	 *
	 * @return  int
	 */ 
	public function getRetry()
	{
		return $this->retry;
	}

	/**
	 * Set 失败重试次数，默认为0
	 *
	 * @param  int  $retry  失败重试次数，默认为0
	 *
	 * @return  self
	 */ 
	public function setRetry(int $retry)
	{
		$this->retry = $retry;

		return $this;
	}

	/**
	 * Get 是否使用代理，默认false
	 *
	 * @return  bool
	 */ 
	public function getUseProxy()
	{
		return $this->useProxy;
	}

	/**
	 * Set 是否使用代理，默认false
	 *
	 * @param  bool  $useProxy  是否使用代理，默认false
	 *
	 * @return  self
	 */ 
	public function setUseProxy(bool $useProxy)
	{
		$this->useProxy = $useProxy;

		return $this;
	}

	/**
	 * Get 代理设置
	 *
	 * @return  array
	 */ 
	public function getProxy()
	{
		return $this->proxy;
	}

	/**
	 * Set 代理设置
	 *
	 * @param  array  $proxy  代理设置
	 *
	 * @return  self
	 */ 
	public function setProxy(array $proxy)
	{
		$this->proxy = $proxy;

		return $this;
	}

	/**
	 * 代理
	 * @param string $server 代理服务器地址
	 * @param int $port 代理服务器端口
	 * @param string $type 代理类型，支持：http、socks4、socks4a、socks5
	 * @param string $auth 代理认证方式，支持：basic、ntlm。一般默认basic
	 * @return static 
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
}