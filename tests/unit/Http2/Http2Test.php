<?php
namespace Yurun\Util\YurunHttp\Test\Http2;

use Yurun\Util\HttpRequest;
use Yurun\Util\YurunHttp\Test\BaseTest;

class Http2Test extends BaseTest
{
    public function testHttp2()
    {
        $this->call(function(){
            $http = new HttpRequest;
            $http->protocolVersion = '2.0';
            $date = strtotime('2017-03-24 17:12:14');
            $response = $http->post($this->http2Host, [
                'date'  =>  $date,
            ], 'json');
            $data = $response->json(true);
            $this->assertEquals($date, isset($data['date']) ? $data['date'] : null);
            $this->assertGreaterThan(1, isset($data['fd']) ? $data['fd'] : null);
            $this->assertEquals('yurun', $response->getHeaderLine('trailer'));
            $this->assertEquals('niubi', $response->getHeaderLine('yurun'));
        });
    }

}