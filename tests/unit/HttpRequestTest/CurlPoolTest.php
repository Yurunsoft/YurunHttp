<?php
namespace Yurun\Util\YurunHttp\Test\HttpRequestTest;

use Yurun\Util\HttpRequest;
use Yurun\Util\YurunHttp\Test\BaseTest;
use Yurun\Util\YurunHttp\ConnectionPool;
use Yurun\Util\YurunHttp\Handler\Curl\CurlHttpConnectionManager;

class CurlPoolTest extends BaseTest
{
    public function test()
    {
        $this->call(function(){
            // 启用连接池
            ConnectionPool::enable();

            try {
                // 为这个地址设置限制连接池连接数量3个
                // 一定不要有 / 及后续参数等
                $url = rtrim($this->host, '/');
                ConnectionPool::setConfig($url, 3);

                $http = new HttpRequest;
                $response = $http->get($this->host . '?a=info');
                $data = $response->json(true);
                $remote = $data['remote'] ?? null;
                $this->assertNotNull($remote);

                $pool = CurlHttpConnectionManager::getInstance()->getConnectionPool($url);
                $this->assertEquals(1, $pool->getCount());
                $this->assertEquals(1, $pool->getFree());
                $this->assertEquals(0, $pool->getUsed());

                $http = new HttpRequest;
                $response = $http->get($this->host . '?a=info');
                $data = $response->json(true);

                $this->assertEquals($remote, $data['remote'] ?? null);

                $pool = CurlHttpConnectionManager::getInstance()->getConnectionPool($url);
                $this->assertEquals(1, $pool->getCount());
                $this->assertEquals(1, $pool->getFree());
                $this->assertEquals(0, $pool->getUsed());

            } finally {
                ConnectionPool::disable();
            }
        });
    }
}
