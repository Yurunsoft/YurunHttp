# YurunHttp

## 简介

一个基于PHP cURL的开源HTTP类库，支持链式操作，省去繁杂的cURL使用方法。

## 用法

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
    "lang" => "php",
    "ver"  => "any"
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
    "lang": "php",
    "ver":  "any"
}
EOF;
$http = HttpRequest::newSession();
$http->contentType("application/json");
$response = $http->post('http://www.baidu.com/', $requestBody);
var_dump($response);
```

### POST(multi_part)

```php
<?php
$requestBody = new HttpRequestMultipartBody();
$requestBody->add("name", "php");
$requestBody->addFile("file", "/path/to/aaa.txt", "aaa.txt");
$http = HttpRequest::newSession();
$response = $http->post('http://www.baidu.com/', $requestBody);
var_dump($response);
```

具体详见Demo