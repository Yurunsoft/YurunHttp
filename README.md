# YurunHttp

## 简介

一个基于PHP cURL的开源HTTP类库，支持链式操作，省去繁杂的cURL使用方法，简单易用。

我们有完善的在线技术文档：[http://doc.yurunsoft.com/YurunHttp](http://doc.yurunsoft.com/YurunHttp)

同时欢迎各位加入技术支持群74401592，如有问题可以及时解答和修复。

个人精力有限，欢迎各位来提交PR（[码云](https://gitee.com/yurunsoft/YurunHttp)/[Github](https://github.com/Yurunsoft/YurunHttp)），一起完善YurunHttp，让它能够更加好用。

## Composer

本项目可以使用composer安装，遵循psr-4自动加载规则，在你的 `composer.json` 中加入下面的内容
```json
{
    "require": {
        "yurunsoft/yurun-http": "1.3.*"
    }
}
```

然后执行 `composer update` 安装。

之后你便可以使用 `include "vendor/autoload.php";` 来自动加载类。（ps：不要忘了namespace）

## 用法

### 链式调用

```php
<?php
$http = HttpRequest::newSession();
$response = $http->retry(3) // 失败重试3次
                 ->ua('Mozilla/5.0 (compatible; Baiduspider/2.0; +http://www.baidu.com/search/spider.html)')
                 ->referer('http://www.baidu.com/')
                 ->accept('text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8')
                 ->acceptLanguage('zh-CN,zh;q=0.8')
                 ->get('http://www.baidu.com/');
if($response->success)
{
    $body = $response->body; // 返回的正文内容
    $header = $response->headers; // 返回头
}
else
{
    // 失败输出错误码和错误信息
    echo $response->errno(), ':', $response->error();
}
```

### GET

```php
<?php
$http = HttpRequest::newSession();
$response = $http->get('http://www.baidu.com/');
var_dump($response);
```

### POST(x-www-form-urlencoded)

```php
<?php
$requestBody = array(
    'lang' => 'php',
    'ver'  => 'any'
);
$http = HttpRequest::newSession();
$response = $http->post('http://www.baidu.com/', $requestBody);
var_dump($response);
```

### POST(raw)

```php
<?php
$requestBody = <<<EOF
{
    'lang': 'php',
    'ver':  'any'
}
EOF;
$http = HttpRequest::newSession();
$http->contentType('application/json');
$response = $http->post('http://www.baidu.com/', $requestBody);
var_dump($response);
```

### POST上传文件(multi_part)

```php
<?php
$requestBody = new HttpRequestMultipartBody();
$requestBody->add('name', 'php');
$requestBody->addFile('file', '/path/to/aaa.txt', 'aaa.txt');
$http = HttpRequest::newSession();
$response = $http->post('http://www.baidu.com/', $requestBody);
var_dump($response);
```

### FTP下载

```php
<?php
$url = 'ftp://用户名:密码@IP地址/文件路径';
$url = 'ftp://IP地址/文件路径';
$http = HttpRequest::newSession();
// $http->userPwd('用户名','密码'); // 除了在URL里，也可以用这种方式设置密码
$http->saveFile('./')->get($url); // 使用ftp服务器中的文件名保存到当前目录
$http->saveFile('./abc.txt')->get($url); // 指定文件名保存
```

### 文件下载

```php
<?php
$http = HttpRequest::newSession();
$http->download('baidu.html', 'http://www.baidu.com');
```

### 断点续传分块下载
```php
<?php
try{
	$download = new Download('http://tool.chinaz.com/ChinazSEOTool.zip');
	$download->blockSize = 1048576; // 每一块数据的大小，可以不设置，默认为1M
	// 绑定每一块数据下载完成事件
	$download->on('progressChanged', function($e){
		var_dump($e);
	});
	// 下载
	$download->download(__DIR__ . '/1.zip');
}catch(Exception $e)
{
	var_dump($e->getMessage());
}
```

### 编码转换
```php
<?php
$http = Yurun\Until\HttpRequest::newSession();
var_dump($http->get('http://www.baidu.com/')->body('UTF-8', 'gb2312')); // utf-8转gb2312
```

### 格式化结果
支持jsonp、json、xml
```php
<?php
$http = Yurun\Until\HttpRequest::newSession();
var_dump('jsonp:', $http->get('https://graph.qq.com/oauth2.0/token')->jsonp($assoc));
// 你也可以获取时候进行编码转换，如下代码：gb2312转utf-8
// var_dump('jsonp:', $http->get('https://graph.qq.com/oauth2.0/token')->jsonp($assoc, 'gb2312', 'utf-8'));
```


具体详见Demo