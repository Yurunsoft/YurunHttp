# YurunHttp

[![Latest Version](https://poser.pugx.org/yurunsoft/yurun-http/v/stable)](https://packagist.org/packages/yurunsoft/yurun-http)
[![IMI Doc](https://img.shields.io/badge/docs-passing-green.svg)](http://doc.yurunsoft.com/YurunHttp)
[![IMI License](https://img.shields.io/github/license/Yurunsoft/YurunHttp.svg)](https://github.com/Yurunsoft/YurunHttp/blob/master/LICENSE)

## 简介

YurunHttp 是开源的PHP HTTP类库，支持链式操作，简单易用。

支持所有常见的GET、POST、PUT、DELETE、UPDATE等请求方式，支持上传下载、设置和读取header、Cookie、请求参数、失败重试、限速、代理、证书等。

3.0 版完美支持Curl、Swoole 协程。

我们有完善的在线技术文档：[http://doc.yurunsoft.com/YurunHttp](http://doc.yurunsoft.com/YurunHttp)

API 文档：[https://apidoc.gitee.com/yurunsoft/YurunHttp](https://apidoc.gitee.com/yurunsoft/YurunHttp)

同时欢迎各位加入技术支持群74401592[![点击加群](https://pub.idqqimg.com/wpa/images/group.png "点击加群")](https://shang.qq.com/wpa/qunwpa?idkey=e2e6b49e9a648aae5285b3aba155d59107bb66fde02e229e078bd7359cac8ac3)，如有问题可以及时解答和修复。

个人精力有限，欢迎各位来提交PR（[码云](https://gitee.com/yurunsoft/YurunHttp)/[Github](https://github.com/Yurunsoft/YurunHttp)），一起完善YurunHttp，让它能够更加好用。

## Composer

本项目可以使用composer安装，遵循psr-4自动加载规则，在你的 `composer.json` 中加入下面的内容
```json
{
    "require": {
        "yurunsoft/yurun-http": "~3.0"
    }
}
```

然后执行 `composer update` 安装。

之后你便可以使用 `include "vendor/autoload.php";` 来自动加载类。（ps：不要忘了namespace）

## 用法

### 简单调用

```php
<?php
use Yurun\Util\HttpRequest;

$http = new HttpRequest;
$response = $http->ua('YurunHttp')
                 ->get('http://www.baidu.com');

echo 'html:', PHP_EOL, $response->body();
```

### PSR-7 请求构建

```php
<?php
use Yurun\Util\YurunHttp\Http\Request;
use Yurun\Util\YurunHttp;

$url = 'http://www.baidu.com';

// 构造方法定义：__construct($uri = null, array $headers = [], $body = '', $method = RequestMethod::GET, $version = '1.1', array $server = [], array $cookies = [], array $files = [])
$request = new Request($url);

// 发送请求并获取结果
$response = YurunHttp::send($request);

var_dump($response);
```

### Swoole 协程模式

```php
<?php
use Yurun\Util\YurunHttp;
use Yurun\Util\HttpRequest;

// 设置默认请求处理器为 Swoole
YurunHttp::setDefaultHandler('Yurun\Util\YurunHttp\Handler\Swoole'); // php 5.4
// YurunHttp::setDefaultHandler(\Yurun\Util\YurunHttp\Handler\Swoole::class); // php 5.5+

// Swoole 处理器必须在协程中调用
go('test');

function test()
{
    $http = new HttpRequest;
    $response = $http->get('http://www.baidu.com');
    echo 'html:', PHP_EOL, $response->body();
}
```

具体详见`test`目录中的示例代码

## 捐赠

<img src="https://raw.githubusercontent.com/Yurunsoft/YurunHttp/master/res/pay.png"/>

开源不求盈利，多少都是心意，生活不易，随缘随缘……