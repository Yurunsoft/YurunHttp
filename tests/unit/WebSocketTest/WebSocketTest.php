<?php
namespace Yurun\Util\YurunHttp\Test\WebSocketTest;

use Yurun\Util\YurunHttp;
use Yurun\Util\HttpRequest;
use Yurun\Util\YurunHttp\Test\BaseTest;
use Yurun\Util\YurunHttp\Test\Traits\TSwooleHandlerTest;

class WebSocketTest extends BaseTest
{
    use TSwooleHandlerTest;

    public function testWebSocket()
    {
        $this->call(function(){
            $http = new HttpRequest;
            $client = $http->websocket($this->wsHost);
            $this->assertTrue($client->isConnected());
            $this->assertTrue($client->send(json_encode([
                'action'    =>  'login',
                'username'  =>  'test',
            ])));
            $recv = $client->recv();
            $this->assertEquals('{"success":true}', $recv);
            $time = time();
            $this->assertTrue($client->send(json_encode([
                'action'    =>  'send',
                'message'   =>  $time,
            ])));
            $recv = $client->recv();
            $this->assertEquals('test:' . $time, $recv);
            $client->close();
        });
    }

    public function testWSS()
    {
        $this->call(function(){
            $http = new HttpRequest;
            $client = $http->websocket($this->wssHost);
            $this->assertTrue($client->isConnected());
            $this->assertTrue($client->send(json_encode([
                'action'    =>  'login',
                'username'  =>  'test',
            ])));
            $recv = $client->recv();
            $this->assertEquals('{"success":true}', $recv);
            $time = time();
            $this->assertTrue($client->send(json_encode([
                'action'    =>  'send',
                'message'   =>  $time,
            ])));
            $recv = $client->recv();
            $this->assertEquals('test:' . $time, $recv);
            $client->close();
        });
    }

}