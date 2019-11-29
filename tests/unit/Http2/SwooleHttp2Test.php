<?php
namespace Yurun\Util\YurunHttp\Test\Http2;

use Yurun\Util\HttpRequest;
use Swoole\Coroutine\Channel;
use Yurun\Util\YurunHttp\Http2\SwooleClient;
use Yurun\Util\YurunHttp\Http\Psr7\Uri;
use Yurun\Util\YurunHttp\Test\BaseTest;
use Yurun\Util\YurunHttp\Test\Traits\TSwooleHandlerTest;

class SwooleHttp2Test extends BaseTest
{
    use TSwooleHandlerTest;

    public function testHttp2()
    {
        $this->call(function(){
            $http = new HttpRequest;
            $http->protocolVersion = '2.0';
            $http->timeout = 3000;

            $date = strtotime('2017-03-24 17:12:14');
            $response = $http->post($this->http2Host, [
                'date'  =>  $date,
            ], 'json');
            $data = $response->json(true);
            $this->assertEquals($date, isset($data['date']) ? $data['date'] : null);
            $this->assertGreaterThan(1, isset($data['fd']) ? $data['fd'] : null);
            $this->assertEquals('yurun', $response->getHeaderLine('trailer'));
            $this->assertEquals('niubi', $response->getHeaderLine('yurun'));

            $date = strtotime('2017-03-29 10:50:51');
            $response = $http->post($this->http2Host, [
                'date'  =>  $date,
            ], 'json');
            $data2 = $response->json(true);
            $this->assertEquals($date, isset($data2['date']) ? $data2['date'] : null);
            $this->assertEquals($data['fd'], isset($data2['fd']) ? $data2['fd'] : null);
            $this->assertEquals('yurun', $response->getHeaderLine('trailer'));
            $this->assertEquals('niubi', $response->getHeaderLine('yurun'));
        });
    }

    public function testMuiltCo()
    {
        $this->call(function(){
            $uri = new Uri($this->http2Host);
            $client = new SwooleClient($uri->getHost(), Uri::getServerPort($uri), 'https' === $uri->getScheme());

            $this->assertTrue($client->connect());

            $httpRequest = new HttpRequest;
            $date = strtotime('2017-03-24 17:12:14');
            $request = $httpRequest->buildRequest($this->http2Host, [
                'date'  =>  $date,
            ], 'POST', 'json');

            $streamId = $client->send($request);
            $this->assertGreaterThan(0, $streamId);
            
            $response = $client->recv($streamId, 3);
            $data = $response->json(true);
            $this->assertGreaterThan(1, isset($data['fd']) ? $data['fd'] : null);
            $fd = $data['fd'];

            $count = 10;
            $channel = new Channel($count);
            for($i = 0; $i < $count; ++$i)
            {
                go(function() use($i, $client, $channel, $httpRequest, $fd){
                    $request = $httpRequest->buildRequest($this->http2Host, [
                        'date'  =>  $i,
                    ], 'POST', 'json');
                    $streamId = $client->send($request);
                    $this->assertGreaterThan(0, $streamId);
                    $response = $client->recv($streamId, 3);
                    $data = $response->json(true);
                    $this->assertEquals($fd, isset($data['fd']) ? $data['fd'] : null);
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
    }

}