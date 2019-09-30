<?php
namespace Yurun\Util\YurunHttp\Test\HttpRequestTest;

use Yurun\Util\HttpRequest;
use Yurun\Util\YurunHttp\Test\BaseTest;
use Yurun\Util\YurunHttp\Http\Psr7\UploadedFile;
use Yurun\Util\YurunHttp\Http\Psr7\Consts\MediaType;

class HttpRequestTest extends BaseTest
{
    /**
     * Hello World
     *
     * @return void
     */
    public function testHelloWorld()
    {
        $this->call(function(){
            $http = new HttpRequest;
            $response = $http->get($this->host);
            $this->assertResponse($response);
            $this->assertEquals($response->body(), 'YurunHttp');
        });
    }

    /**
     * JSON
     *
     * @return void
     */
    public function testJson()
    {
        $this->call(function(){
            $http = new HttpRequest;
            $response = $http->get($this->host . '?a=info');
            $this->assertResponse($response);
            $data = $response->json(true);
            $this->assertArrayHasKey('get', $data);
        });
    }

    /**
     * $_GET
     *
     * @return void
     */
    public function testGetParams()
    {
        $this->call(function(){
            $http = new HttpRequest;
            $time = time();
            $response = $http->get($this->host . '?a=info&time=' . $time);
            $this->assertResponse($response);
            $data = $response->json(true);
            $this->assertEquals('GET', isset($data['server']['REQUEST_METHOD']) ? $data['server']['REQUEST_METHOD'] : null);
            $this->assertEquals($time, isset($data['get']['time']) ? $data['get']['time'] : null);
        });
    }

    /**
     * $_GET
     *
     * @return void
     */
    public function testGetParams2()
    {
        $this->call(function(){
            $http = new HttpRequest;
            $time = time();
            $response = $http->get($this->host, [
                'a'     =>  'info',
                'time'  =>  $time,
            ]);
            $this->assertResponse($response);
            $data = $response->json(true);
            $this->assertEquals('GET', isset($data['server']['REQUEST_METHOD']) ? $data['server']['REQUEST_METHOD'] : null);
            $this->assertEquals($time, isset($data['get']['time']) ? $data['get']['time'] : null);
        });
    }

    /**
     * $_POST
     *
     * @return void
     */
    public function testPostParams()
    {
        $this->call(function(){
            $http = new HttpRequest;
            $time = time();
            $response = $http->post($this->host . '?a=info', [
                'time'  =>  $time,
            ]);
            $this->assertResponse($response);
            $data = $response->json(true);
            $this->assertEquals('POST', isset($data['server']['REQUEST_METHOD']) ? $data['server']['REQUEST_METHOD'] : null);
            $this->assertEquals($time, isset($data['post']['time']) ? $data['post']['time'] : null);
        });
    }

    /**
     * PUT 请求
     *
     * @return void
     */
    public function testPutRequest()
    {
        $this->call(function(){
            $http = new HttpRequest;
            $response = $http->put($this->host . '?a=info');
            $this->assertResponse($response);
            $data = $response->json(true);
            $this->assertEquals('PUT', isset($data['server']['REQUEST_METHOD']) ? $data['server']['REQUEST_METHOD'] : null);
        });
    }

    /**
     * $_COOKIE
     *
     * @return void
     */
    public function testCookieParams()
    {
        $this->call(function(){
            $http = new HttpRequest;
            $time = time();
            $hash = uniqid();
            $response = $http->cookie('hash', $hash)
                                ->cookies([
                                    'time'  =>  $time,
                                ])
                                ->get($this->host . '?a=info');
            $this->assertResponse($response);
            $data = $response->json(true);
            $this->assertEquals($time, isset($data['cookie']['time']) ? $data['cookie']['time'] : null);
            $this->assertEquals($hash, isset($data['cookie']['hash']) ? $data['cookie']['hash'] : null);
        });
    }

    /**
     * Request Header
     *
     * @return void
     */
    public function testRequestHeaders()
    {
        $this->call(function(){
            $http = new HttpRequest;
            $time = (string)time();
            $hash = uniqid();
            $response = $http->header('hash', $hash)
                                ->headers([
                                    'time'  =>  $time,
                                ])
                                ->get($this->host . '?a=info');
            $this->assertResponse($response);
            $data = $response->json(true);
            $this->assertEquals($time, isset($data['server']['HTTP_TIME']) ? $data['server']['HTTP_TIME'] : null);
            $this->assertEquals($hash, isset($data['server']['HTTP_HASH']) ? $data['server']['HTTP_HASH'] : null);
        });
    }

    /**
     * Response Header
     *
     * @return void
     */
    public function testResponseHeaders()
    {
        $this->call(function(){
            $http = new HttpRequest;
            $response = $http->get($this->host . '?a=info');
            $this->assertResponse($response);
            $this->assertEquals('one suo', $response->getHeaderLine('Yurun-Http'));
        });
    }

    /**
     * Cookie Manager
     *
     * @return void
     */
    public function testCookieManager()
    {
        $this->call(function(){
            $http = new HttpRequest;
            
            $http->get($this->host . '?a=setCookie');

            sleep(1);

            $response = $http->get($this->host . '?a=info');
            $this->assertResponse($response);
            $data = $response->json(true);

            $compareCookie = [
                'a' =>  '1',
                'c' =>  '3',
            ];

            $this->assertEquals($data['cookie'], $compareCookie);

            $cookieManager = $http->getHandler()->getCookieManager();

            $cookieItem = $cookieManager->getCookieItem('a');
            $this->assertNotNull($cookieItem);
            $this->assertEquals(false, $cookieItem->httpOnly);

            $cookieItem = $cookieManager->getCookieItem('g');
            $this->assertNotNull($cookieItem);
            $this->assertEquals(true, $cookieItem->httpOnly);
        });
    }

    /**
     * AutoRedirect
     *
     * @return void
     */
    public function testAutoRedirect()
    {
        $this->call(function(){
            $http = new HttpRequest;
            
            foreach([301, 302] as $statusCode)
            {
                $time = time();
                $response = $http->post($this->host . '?a=redirect' . $statusCode, 'time=' . $time);
                $this->assertResponse($response);
                $data = $response->json(true);
                $this->assertEquals('GET', $data['server']['REQUEST_METHOD'], $statusCode . ' method error');
            }
            
            foreach([307, 308] as $statusCode)
            {
                $time = time();
                $response = $http->post($this->host . '?a=redirect' . $statusCode, 'time=' . $time);
                $this->assertResponse($response);
                $data = $response->json(true);
                $this->assertEquals('POST', $data['server']['REQUEST_METHOD'], $statusCode . ' method error');
            }

        });
    }

    /**
     * disableAutoRedirect
     *
     * @return void
     */
    public function testDisableAutoRedirect()
    {
        $this->call(function(){
            $http = new HttpRequest;
            $http->followLocation = false;
            
            $response = $http->post($this->host . '?a=redirect301');
            $this->assertResponse($response);
            $this->assertEquals('/?a=info', $response->getHeaderLine('location'));
        });
    }

    /**
     * Limit MaxRedirects
     *
     * @return void
     */
    public function testLimitMaxRedirects()
    {
        $this->call(function(){
            $http = new HttpRequest;
            $http->maxRedirects = 0;

            $response = $http->post($this->host . '?a=redirect301');
            $this->assertEquals('Maximum (0) redirects followed', $response->error());
        });
    }

    /**
     * Upload single file
     *
     * @return void
     */
    public function testUploadSingle()
    {
        $this->call(function(){
            $http = new HttpRequest;
            $file = new UploadedFile(basename(__FILE__), MediaType::TEXT_HTML, __FILE__);
            $http->content([
                'file'  => $file,
            ]);
            $response = $http->post($this->host . '?a=info');
            $this->assertResponse($response);
            $data = $response->json(true);

            $this->assertTrue(isset($data['files']['file']));
            $file = $data['files']['file'];
            $content = file_get_contents(__FILE__);
            $this->assertEquals(strlen($content), $file['size']);
            $this->assertEquals(md5($content), $file['hash']);
            $this->assertEquals(MediaType::TEXT_HTML, $file['type']);
        });
    }

    /**
     * Upload multi files
     *
     * @return void
     */
    public function testUploadMulti()
    {
        $this->call(function(){
            $http = new HttpRequest;
            $file2Path = __DIR__ . '/1.txt';
            $file1 = new UploadedFile(basename(__FILE__), MediaType::TEXT_HTML, __FILE__);
            $file2 = new UploadedFile('1.txt', MediaType::TEXT_PLAIN, $file2Path);
            $http->content([
                'file1' => $file1,
                'file2' => $file2,
            ]);
            $response = $http->post($this->host . '?a=info');
            $this->assertResponse($response);
            $data = $response->json(true);

            $this->assertTrue(isset($data['files']['file1']));
            $file1 = $data['files']['file1'];
            $content = file_get_contents(__FILE__);
            $this->assertEquals(strlen($content), $file1['size']);
            $this->assertEquals(md5($content), $file1['hash']);
            $this->assertEquals(MediaType::TEXT_HTML, $file1['type']);
    
            $this->assertTrue(isset($data['files']['file2']));
            $file2 = $data['files']['file2'];
            $content = file_get_contents($file2Path);
            $this->assertEquals(strlen($content), $file2['size']);
            $this->assertEquals(md5($content), $file2['hash']);
            $this->assertEquals(MediaType::TEXT_PLAIN, $file2['type']);
        });
    }

    /**
     * Download file
     *
     * @return void
     */
    public function testDownload()
    {
        $this->call(function(){
            $http = new HttpRequest;
            $fileName = __DIR__ . '/download.txt';
            if(is_file($fileName))
            {
                unlink($fileName);
            }
            $this->assertFalse(is_file($fileName));
            $http->download($fileName, $this->host . '?a=download1', 'yurunhttp=nb', 'POST');
            $this->assertTrue(is_file($fileName));
            $this->assertEquals('YurunHttp Hello World', file_get_contents($fileName));
        });
    }

    /**
     * Download file auto extension
     *
     * @return void
     */
    public function testDownloadAutoExt()
    {
        $this->call(function(){
            $http = new HttpRequest;
            $fileName = __DIR__ . '/download.html';
            if(is_file($fileName))
            {
                unlink($fileName);
            }
            $this->assertFalse(is_file($fileName));
            $http->download(__DIR__ . '/download.*', $this->host . '?a=download2', 'yurunhttp=nb', 'POST');
            $this->assertTrue(is_file($fileName));
            $this->assertEquals('<h1>YurunHttp Hello World</h1>', file_get_contents($fileName));
        });
    }

}