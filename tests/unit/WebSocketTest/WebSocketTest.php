<?php

namespace Yurun\Util\YurunHttp\Test\WebSocketTest;

use Yurun\Util\HttpRequest;
use Yurun\Util\YurunHttp\Test\BaseTest;
use Yurun\Util\YurunHttp\Test\Traits\TSwooleHandlerTest;

class WebSocketTest extends BaseTest
{
    use TSwooleHandlerTest;

    public function testWebSocket(): void
    {
        $this->call(function () {
            $http = new HttpRequest();
            $client = $http->websocket($this->wsHost);
            $this->assertTrue($client->isConnected());
            $this->assertTrue($client->send(json_encode([
                'action'    => 'login',
                'username'  => 'test',
            ])));
            $recv = $client->recv();
            $this->assertEquals('{"success":true}', $recv);
            $time = time();
            $this->assertTrue($client->send(json_encode([
                'action'    => 'send',
                'message'   => $time,
            ])));
            $recv = $client->recv();
            $this->assertEquals('test:' . $time, $recv);
            $client->close();
        });
    }

    public function testWSS(): void
    {
        $this->call(function () {
            $http = new HttpRequest();
            $client = $http->websocket($this->wssHost);
            $this->assertTrue($client->isConnected());
            $this->assertTrue($client->send(json_encode([
                'action'    => 'login',
                'username'  => 'test',
            ])));
            $recv = $client->recv();
            $this->assertEquals('{"success":true}', $recv);
            $time = time();
            $this->assertTrue($client->send(json_encode([
                'action'    => 'send',
                'message'   => $time,
            ])));
            $recv = $client->recv();
            $this->assertEquals('test:' . $time, $recv);
            $client->close();
        });
    }

    public function testMemoryLeak(): void
    {
        $this->call(function () {
            $memorys = [1, 2, 3, 4, 5];
            for ($i = 0; $i < 5; ++$i)
            {
                $http = new HttpRequest();
                $client = $http->websocket($this->wsHost);
                $client->close();
                $memorys[$i] = memory_get_usage();
            }
            unset($memorys[0]);
            $this->assertCount(1, array_unique($memorys));
        });
    }
}
