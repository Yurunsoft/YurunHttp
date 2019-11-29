<?php
/**
 * 使用 PSR-7 标准构建请求扩展示例
 */
require dirname(__DIR__) . '/vendor/autoload.php';

use Yurun\Util\YurunHttp;
use Yurun\Util\YurunHttp\Attributes;
use Yurun\Util\YurunHttp\Http\Request;

test();

function test()
{
    $url = 'http://www.baidu.com';

    // 全局参数，一旦设置，所有请求都会使用该参数
    // YurunHttp::swithAttribute(Attributes::CUSTOM_LOCATION, true);

    $request = new Request($url);

    // 以下参数都为可选，示例中的值为默认值
    $request = $request
                        // 自定义重定向，仅Curl模式有效，一般不推荐使用。Swoole模式强制自定义重定向
                        ->withAttribute(Attributes::CUSTOM_LOCATION, false)
                        // 设置是否允许重定向。如果为true，则遇到301或302进行重定向操作。如果为false则不重定向。
                        ->withAttribute(Attributes::FOLLOW_LOCATION, true)
                        // 最大重定向次数，防止无限重定向卡死
                        ->withAttribute(Attributes::MAX_REDIRECTS, 10)
                        // 失败重试次数
                        ->withAttribute(Attributes::RETRY, 0)
                        // 是否验证证书
                        ->withAttribute(Attributes::IS_VERIFY_CA, false)
                        // CA证书路径
                        ->withAttribute(Attributes::CA_CERT, null)
                        // SSL证书路径
                        ->withAttribute(Attributes::CERT_PATH, '')
                        // SSL证书密码，Swoole暂不支持
                        ->withAttribute(Attributes::CERT_PASSWORD, null)
                        // SSL证书类型，仅Curl有效，支持的格式有"PEM" (默认值), "DER"和"ENG"。Swoole仅支持PEM
                        ->withAttribute(Attributes::CERT_TYPE, 'pem')
                        // SSL私钥路径
                        ->withAttribute(Attributes::KEY_PATH, '')
                        // SSL私钥密码，Swoole暂不支持
                        ->withAttribute(Attributes::KEY_PASSWORD, null)
                        // SSL私钥类型，仅Curl有效，支持的格式有"PEM" (默认值), "DER"和"ENG"。Swoole仅支持PEM
                        ->withAttribute(Attributes::KEY_TYPE, 'pem')
                        // 扩展设置。用途：Curl:curl_setopt_array()；Swoole：Client->set()
                        ->withAttribute(Attributes::OPTIONS, [])
                        // 保存文件路径，不为null则将请求结果的body保存到文件
                        ->withAttribute(Attributes::SAVE_FILE_PATH, null)
                        // 保存文件读写模式
                        ->withAttribute(Attributes::SAVE_FILE_MODE, 'w+')
                        // 是否使用代码
                        ->withAttribute(Attributes::USE_PROXY, false)
                        // 代理类型。Curl支持http、socks4、socks4a、socks5；Swoole支持http和socks5
                        ->withAttribute(Attributes::PROXY_TYPE, 'http')
                        // 代理认证方式。Curl支持basic和ntlm；Swoole仅支持basic
                        ->withAttribute(Attributes::PROXY_AUTH, 'basic')
                        // 代理服务器地址
                        ->withAttribute(Attributes::PROXY_SERVER, null)
                        // 代理服务器端口
                        ->withAttribute(Attributes::PROXY_PORT, null)
                        // 代理账号
                        ->withAttribute(Attributes::PROXY_USERNAME, '')
                        // 代理密码
                        ->withAttribute(Attributes::PROXY_PASSWORD, '')
                        // http认证的用户名
                        ->withAttribute(Attributes::USERNAME, null)
                        // http认证的密码
                        ->withAttribute(Attributes::PASSWORD, '')
                        // 连接超时时间，单位：毫秒，仅Curl有效
                        ->withAttribute(Attributes::CONNECT_TIMEOUT, 30000)
                        // 总超时时间，单位：毫秒
                        ->withAttribute(Attributes::TIMEOUT, 30000)
                        // 下载限速，单位：字节，仅Curl有效
                        ->withAttribute(Attributes::DOWNLOAD_SPEED, 30000)
                        // 上传限速，单位：字节，仅Curl有效
                        ->withAttribute(Attributes::UPLOAD_SPEED, 30000)
                        ;
    $response = YurunHttp::send($request);
    var_dump($response);
}
