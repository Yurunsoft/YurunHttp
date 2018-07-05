<?php
/**
 * 使用 PSR-7 标准构建请求扩展示例
 */
require dirname(__DIR__) . '/vendor/autoload.php';

use Yurun\Util\YurunHttp\Http\Request;
use Yurun\Util\YurunHttp;

test();

function test()
{
	$url = 'http://www.baidu.com';

	$request = new Request($url);

	// 以下参数都为可选，示例中的值为默认值
	$request = $request
						// 自定义重定向，仅Curl模式有效，一般不推荐使用。Swoole模式强制自定义重定向
						->withAttribute('customLocation', false)
						// 设置是否允许重定向。如果为true，则遇到301或302进行重定向操作。如果为false则不重定向。
						->withAttribute('followLocation', true)
						// 最大重定向次数，防止无限重定向卡死
						->withAttribute('maxRedirects', 10)
						// 失败重试次数
						->withAttribute('retry', 0)
						// 是否验证证书
						->withAttribute('isVerifyCA', false)
						// CA证书路径
						->withAttribute('caCert', null)
						// SSL证书路径
						->withAttribute('certPath', '')
						// SSL证书密码，Swoole暂不支持
						->withAttribute('certPassword', null)
						// SSL证书类型，仅Curl有效，支持的格式有"PEM" (默认值), "DER"和"ENG"。Swoole仅支持PEM
						->withAttribute('certType', 'pem')
						// SSL私钥路径
						->withAttribute('keyPath', '')
						// SSL私钥密码，Swoole暂不支持
						->withAttribute('keyPassword', null)
						// SSL私钥类型，仅Curl有效，支持的格式有"PEM" (默认值), "DER"和"ENG"。Swoole仅支持PEM
						->withAttribute('keyType', 'pem')
						// 扩展设置。用途：Curl:curl_setopt_array()；Swoole：Client->set()
						->withAttribute('options', [])
						// 保存文件路径，不为null则将请求结果的body保存到文件
						->withAttribute('saveFilePath', null)
						// 保存文件读写模式
						->withAttribute('saveFileMode', 'w+')
						// 是否使用代码
						->withAttribute('useProxy', false)
						// 代理类型。Curl支持http、socks4、socks4a、socks5；Swoole支持http和socks5
						->withAttribute('proxy.type', 'http')
						// 代理认证方式。Curl支持basic和ntlm；Swoole仅支持basic
						->withAttribute('proxy.auth', 'basic')
						// 代理服务器地址
						->withAttribute('proxy.server', null)
						// 代理服务器端口
						->withAttribute('proxy.port', null)
						// 代理账号
						->withAttribute('proxy.username', '')
						// 代理密码
						->withAttribute('proxy.password', '')
						// http认证的用户名
						->withAttribute('username', null)
						// http认证的密码
						->withAttribute('password', '')
						// 连接超时时间，单位：毫秒，仅Curl有效
						->withAttribute('connectTimeout', 30000)
						// 总超时时间，单位：毫秒
						->withAttribute('timeout', 30000)
						// 下载限速，单位：字节，仅Curl有效
						->withAttribute('downloadSpeed', 30000)
						// 上传限速，单位：字节，仅Curl有效
						->withAttribute('uploadSpeed', 30000)
						;
	$response = YurunHttp::send($request);
	var_dump($response);
}
