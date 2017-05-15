<?php
/**
 * Created by PhpStorm.
 * User: xiaozhuai
 * Date: 17/4/23
 * Time: 下午2:58
 */

include '../HttpRequestMultipartBody.php';
include '../HttpRequest.php';
include '../HttpResponse.php';

$multipartBody = new \Yurun\Until\HttpRequestMultipartBody();
$multipartBody->add('aaa', 'bbb');
$multipartBody->addFile('file', __DIR__ . '/test_multipart.php', 'test_multipart.php');

$request = \Yurun\Until\HttpRequest::newSession();
$response = $request->post('http://localhost:9999/multi.php', $multipartBody);

var_dump($response->body);
