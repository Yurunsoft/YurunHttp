# YurunHttp

## 简介

一个基于PHP cURL的开源HTTP类库，支持链式操作，省去繁杂的cURL使用方法。

## Composer

本项目可以使用composer安装，遵循psr-4自动加载规则，在你的 `composer.json` 中加入下面的内容
```json
{
    "require": {
        "jtunggit/yurun-http": "dev-master"
    }
}
```

然后执行 `composer install` 安装。

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
var_dump($response);
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

### POST(multi_part)

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
具体详见Demo
