<?php
/**
 * json body
 */
require dirname(__DIR__) . '/vendor/autoload.php';

use Yurun\Util\HttpRequest;

$http = new HttpRequest;
$response = $http->post('http://www.baidu.com', [
    'id'    =>  1,
], 'json');
echo 'html:', PHP_EOL, $response->body();
