<?php
/**
 * swoole 并发请求
 */

use Yurun\Util\YurunHttp;
use Yurun\Util\HttpRequest;
use Yurun\Util\YurunHttp\Co\Batch;

require dirname(__DIR__) . '/vendor/autoload.php';

YurunHttp::setDefaultHandler(\Yurun\Util\YurunHttp\Handler\Swoole::class);

go(function(){
    $result = Batch::run([
        (new HttpRequest)->url('https://www.imiphp.com'),
        (new HttpRequest)->url('https://www.yurunsoft.com'),
    ]);
    
    var_dump($result[0]->getHeaders(), strlen($result[0]->body()), $result[0]->getStatusCode());
    
    var_dump($result[1]->getHeaders(), strlen($result[1]->body()), $result[1]->getStatusCode());
});