<?php
namespace Yurun\Util\YurunHttp\Test\Http2;

use Yurun\Util\HttpRequest;
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

}