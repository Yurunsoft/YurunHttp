<?php
/**
 * Created by PhpStorm.
 * User: xiaozhuai
 * Date: 17/4/23
 * Time: 下午2:58
 */
// 文件上传
require dirname(__DIR__) . '/vendor/autoload.php';

$multipartBody = new \Yurun\Until\HttpRequestMultipartBody();
$multipartBody->add('aaa', 'bbb');
$multipartBody->addFile('file', __DIR__ . '/test_multipart.php', 'test_multipart.php');
$multipartBody->addFileContent('file2', '我是文件内容', 'fileName.txt');

$request = \Yurun\Until\HttpRequest::newSession();
$response = $request->post('http://localhost:9999/multi.php', $multipartBody);

var_dump($response->body);
