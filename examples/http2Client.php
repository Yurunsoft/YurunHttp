<?php
/**
 * Http Client
 */

use Yurun\Util\HttpRequest;
use Swoole\Coroutine\Channel;
use Yurun\Util\YurunHttp\Http\Psr7\Uri;

require dirname(__DIR__) . '/vendor/autoload.php';

go(function(){
    $uri = new Uri('http://www.taobao.com/');

    $client = new \Yurun\Util\YurunHttp\Http2\SwooleClient($uri->getHost(), Uri::getServerPort($uri), 'https' === $uri->getScheme());
    $client->connect();

    // 接收服务端主动推送
    $client->setServerPushQueueLength(16); // 接收服务端推送的队列长度
    go(function() use($client){
        do {
            $response = $client->recv();
            var_dump($response->body());
        } while($response->success);
    });

    // 客户端请求和响应获取
    $httpRequest = new HttpRequest;
    
    $count = 10;
    $channel = new Channel($count);
    for($i = 0; $i < $count; ++$i)
    {
        go(function() use($i, $client, $channel, $httpRequest, $uri){
            $request = $httpRequest->header('aaa', 'bbb')->buildRequest($uri, [
                'date'  =>  $i,
            ], 'POST', 'json');
            $streamId = $client->send($request);
            var_dump('send:' . $streamId);
            $response = $client->recv($streamId, 3);
            $content = $response->body();
            var_dump('recv:' . $response->getStreamId() . ';statusCode:'. $response->getStatusCode(), ';contentLength:' . strlen($content));
            $channel->push(1);
        });
    }
    $returnCount = 0;
    do {
        if($channel->pop())
        {
            ++$returnCount;
        }
    } while($returnCount < $count);

    $client->close();
});
