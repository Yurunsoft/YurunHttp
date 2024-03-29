<?php

namespace Yurun\Util\YurunHttp\Test\HttpRequestTest;

use Swoole\Coroutine;
use Yurun\Util\HttpRequest;
use Yurun\Util\YurunHttp;
use Yurun\Util\YurunHttp\Co\Batch;
use Yurun\Util\YurunHttp\HandlerOptions;
use Yurun\Util\YurunHttp\Http\Psr7\Consts\MediaType;
use Yurun\Util\YurunHttp\Http\Psr7\UploadedFile;
use Yurun\Util\YurunHttp\Http\Psr7\Uri;
use Yurun\Util\YurunHttp\Http\Request;
use Yurun\Util\YurunHttp\Test\BaseTest;

class HttpRequestTest extends BaseTest
{
    /**
     * Hello World.
     *
     * @return void
     */
    public function testHelloWorld(): void
    {
        $this->call(function () {
            $http = new HttpRequest();
            $response = $http->get($this->host);
            $this->assertResponse($response);
            $this->assertEquals('YurunHttp', $response->body());
        });
    }

    /**
     * JSON.
     *
     * @return void
     */
    public function testJson(): void
    {
        $this->call(function () {
            $http = new HttpRequest();
            $response = $http->get($this->host . '?a=info');
            $this->assertResponse($response);
            $data = $response->json(true);
            $this->assertArrayHasKey('get', $data);
        });
    }

    /**
     * $_GET.
     *
     * @return void
     */
    public function testGetParams(): void
    {
        $this->call(function () {
            $http = new HttpRequest();
            $time = time();
            $response = $http->get($this->host . '?a=info&time=' . $time);
            $this->assertResponse($response);
            $data = $response->json(true);
            $this->assertEquals('GET', isset($data['method']) ? $data['method'] : null);
            $this->assertEquals($time, isset($data['get']['time']) ? $data['get']['time'] : null);
        });
    }

    /**
     * $_GET.
     *
     * @return void
     */
    public function testGetParams2(): void
    {
        $this->call(function () {
            $http = new HttpRequest();
            $time = time();
            $response = $http->get($this->host, [
                'a'     => 'info',
                'time'  => $time,
            ]);
            $this->assertResponse($response);
            $data = $response->json(true);
            $this->assertEquals('GET', isset($data['method']) ? $data['method'] : null);
            $this->assertEquals($time, isset($data['get']['time']) ? $data['get']['time'] : null);
        });
    }

    /**
     * $_POST.
     *
     * @return void
     */
    public function testPostParams(): void
    {
        $this->call(function () {
            $http = new HttpRequest();
            $time = (string) time();
            $response = $http->post($this->host . '?a=info', [
                'time'  => $time,
            ]);
            $this->assertResponse($response);
            $data = $response->json(true);
            $this->assertEquals('POST', isset($data['method']) ? $data['method'] : null);
            $this->assertEquals($time, isset($data['post']['time']) ? $data['post']['time'] : null);

            $http = new HttpRequest();
            $time = (string) time();
            $params = new \stdClass();
            $params->time = $time;
            $this->assertResponse($response);
            $data = $response->json(true);
            $this->assertEquals('POST', isset($data['method']) ? $data['method'] : null);
            $this->assertEquals($time, isset($data['post']['time']) ? $data['post']['time'] : null);
        });
    }

    /**
     * PUT 请求
     *
     * @return void
     */
    public function testPutRequest(): void
    {
        $this->call(function () {
            $http = new HttpRequest();
            $response = $http->put($this->host . '?a=info', 'test');
            $this->assertResponse($response);
            $data = $response->json(true);
            $this->assertEquals('PUT', isset($data['method']) ? $data['method'] : null);
        });
    }

    /**
     * $_COOKIE.
     *
     * @return void
     */
    public function testCookieParams(): void
    {
        $this->call(function () {
            $http = new HttpRequest();
            $time = time();
            $hash = uniqid();
            $response = $http->cookie('hash', $hash)
                                ->cookies([
                                    'time'  => $time,
                                ])
                                ->get($this->host . '?a=info');
            $this->assertResponse($response);
            $data = $response->json(true);
            $this->assertEquals($time, isset($data['cookie']['time']) ? $data['cookie']['time'] : null);
            $this->assertEquals($hash, isset($data['cookie']['hash']) ? $data['cookie']['hash'] : null);
        });
    }

    /**
     * Request Header.
     *
     * @return void
     */
    public function testRequestHeaders(): void
    {
        $this->call(function () {
            $http = new HttpRequest();
            $time = (string) time();
            $hash = uniqid();
            $hash2 = uniqid();
            $response = $http->header('hash', $hash)
                                ->headers([
                                    'time'  => $time,
                                ])
                                ->rawHeader('hash2: ' . $hash2)
                                ->rawHeaders([
                                    'a: 1',
                                ])
                                ->get($this->host . '?a=info');
            $this->assertResponse($response);
            $data = $response->json(true);
            $this->assertEquals($time, isset($data['header']['time']) ? $data['header']['time'] : null);
            $this->assertEquals($hash, isset($data['header']['hash']) ? $data['header']['hash'] : null);
            $this->assertEquals($hash2, isset($data['header']['hash2']) ? $data['header']['hash2'] : null);
            $this->assertEquals('1', isset($data['header']['a']) ? $data['header']['a'] : null);
        });
    }

    /**
     * Response Header.
     *
     * @return void
     */
    public function testResponseHeaders(): void
    {
        $this->call(function () {
            $http = new HttpRequest();
            $response = $http->get($this->host . '?a=info');
            $this->assertResponse($response);
            $this->assertEquals('one suo', $response->getHeaderLine('Yurun-Http'));
        });
    }

    /**
     * Cookie Manager.
     *
     * @return void
     */
    public function testCookieManager(): void
    {
        $this->call(function () {
            $http = new HttpRequest();

            $http->get($this->host . '?a=setCookie');

            static $compareCookie = [
                'a' => '1',
                'c' => '3',
            ];

            $data = null;
            for ($i = 0; $i < 2; ++$i)
            {
                sleep(1);

                $response = $http->get($this->host . '?a=info');
                $this->assertResponse($response);
                $data = $response->json(true);

                if ($compareCookie === $data['cookie'])
                {
                    break;
                }
            }
            $this->assertEquals($compareCookie, $data['cookie']);

            $cookieManager = $http->getHandler()->getCookieManager();

            $cookieItem = $cookieManager->getCookieItem('a');
            $this->assertNotNull($cookieItem);
            $this->assertFalse($cookieItem->httpOnly);

            $this->assertNull($cookieManager->getCookieItem('b'));

            $cookieItem = $cookieManager->getCookieItem('g');
            $this->assertNotNull($cookieItem);
            $this->assertTrue($cookieItem->httpOnly);
        });
    }

    /**
     * AutoRedirect.
     *
     * @return void
     */
    public function testAutoRedirect(): void
    {
        $this->call(function () {
            $http = new HttpRequest();

            foreach ([301, 302] as $statusCode)
            {
                $response = $http->post($this->host . '?a=redirect' . $statusCode);
                $this->assertResponse($response);
                $data = $response->json(true);
                $this->assertEquals('GET', $data['method'], $statusCode . ' method error');
            }

            foreach ([307, 308] as $statusCode)
            {
                $response = $http->post($this->host . '?a=redirect' . $statusCode);
                $this->assertResponse($response);
                $data = $response->json(true);
                $this->assertEquals('POST', $data['method'], $statusCode . ' method error');
            }
        });
    }

    /**
     * disableAutoRedirect.
     *
     * @return void
     */
    public function testDisableAutoRedirect(): void
    {
        $this->call(function () {
            $http = new HttpRequest();
            $http->followLocation = false;

            $response = $http->post($this->host . '?a=redirect301');
            $this->assertResponse($response);
            $this->assertEquals('/?a=info', $response->getHeaderLine('location'));
        });
    }

    /**
     * Limit MaxRedirects.
     *
     * @return void
     */
    public function testLimitMaxRedirects(): void
    {
        $this->call(function () {
            $http = new HttpRequest();
            $http->maxRedirects = 0;

            $response = $http->post($this->host . '?a=redirect301');
            $this->assertEquals('Maximum (0) redirects followed', $response->error());
        });
    }

    /**
     * Redirect Other.
     *
     * @return void
     */
    public function testRedirectOther(): void
    {
        $this->call(function () {
            $http = new HttpRequest();

            $response = $http->post($this->host . '?a=redirectOther');
            $data = $response->json(true);
            $this->assertEquals([
                'id'    => '1',
            ], isset($data['args']) ? $data['args'] : null);
        });
    }

    /**
     * @return void
     */
    public function testRedirectCookie(): void
    {
        $this->call(function () {
            $http = new HttpRequest();

            $response = $http->post($this->host . '?a=redirectCookie');
            $data = $response->json(true);
            $this->assertEquals('1', isset($data['cookie']['redirectCookie']) ? $data['cookie']['redirectCookie'] : null);
            $cookieItem = $http->getHandler()->getCookieManager()->getCookieItem('redirectCookie');
            $this->assertNotNull($cookieItem);
            $this->assertEquals('1', $cookieItem->value);
        });
    }

    /**
     * Upload single file.
     *
     * @return void
     */
    public function testUploadSingle(): void
    {
        $this->call(function () {
            $http = new HttpRequest();
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
            $this->assertEquals(\strlen($content), $file['size']);
            $this->assertEquals(md5($content), $file['hash']);
            $this->assertEquals(MediaType::TEXT_HTML, $file['type']);
        });
    }

    /**
     * Upload multi files.
     *
     * @return void
     */
    public function testUploadMulti(): void
    {
        $this->call(function () {
            $http = new HttpRequest();
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
            $this->assertEquals(\strlen($content), $file1['size']);
            $this->assertEquals(md5($content), $file1['hash']);
            $this->assertEquals(MediaType::TEXT_HTML, $file1['type']);

            $this->assertTrue(isset($data['files']['file2']));
            $file2 = $data['files']['file2'];
            $content = file_get_contents($file2Path);
            $this->assertEquals(\strlen($content), $file2['size']);
            $this->assertEquals(md5($content), $file2['hash']);
            $this->assertEquals(MediaType::TEXT_PLAIN, $file2['type']);
        });
    }

    /**
     * body.
     *
     * @return void
     */
    public function testBody(): void
    {
        $this->call(function () {
            $http = new HttpRequest();
            $data = [
                'id'    => 1,
                'name'  => 'YurunHttp',
            ];
            $response = $http->post($this->host . '?a=body', $data, 'json');
            $this->assertResponse($response);
            $this->assertEquals(json_encode($data), $response->body());

            $http = new HttpRequest();
            $data = new \stdClass();
            $data->id = 1;
            $data->name = 'YurunHttp';
            $response = $http->post($this->host . '?a=body', $data, 'json');
            $this->assertResponse($response);
            $this->assertEquals(json_encode($data), $response->body());
        });
    }

    /**
     * $response->getRequest().
     *
     * @return void
     */
    public function testResponseGetRequest(): void
    {
        $this->call(function () {
            $http = new HttpRequest();
            $response = $http->get($this->host);
            $this->assertResponse($response);
            $this->assertEquals('YurunHttp', $response->body());
            $this->assertNotNull($response->getRequest());
            $this->assertEquals($this->host, $response->getRequest()->getUri());
        });
    }

    public function test304(): void
    {
        $this->call(function () {
            /* @phpstan-ignore-next-line */
            if (method_exists(Coroutine::class, 'getuid') && Coroutine::getuid() > 0 && version_compare(\SWOOLE_VERSION, '4.4.17', '<'))
            {
                $this->markTestSkipped('Swoole must >= 4.4.17');
            }
            $http = new HttpRequest();
            $response = $http->get($this->host . '?a=304');
            $this->assertResponse($response);
            $this->assertEquals(304, $response->getStatusCode());
            $this->assertEquals('', $response->body());
        });
    }

    public function testUriWithAuth(): void
    {
        $this->call(function () {
            $http = new HttpRequest();
            $uri = (new Uri($this->host . '?a=auth'))->withUserInfo('test', '123456');
            $response = $http->get($uri);
            $this->assertResponse($response);
            $this->assertEquals('Basic ' . base64_encode('test:123456'), $response->body());
        });
    }

    /**
     * Download file.
     *
     * @return void
     */
    public function testDownload(): void
    {
        $this->call(function () {
            $http = new HttpRequest();
            $fileName = __DIR__ . '/download.txt';
            if (is_file($fileName))
            {
                unlink($fileName);
            }
            $this->assertFalse(is_file($fileName));
            $response = $http->download($fileName, $this->host . '?a=download1', 'yurunhttp=nb', 'POST');
            $this->assertEquals($fileName, $response->getSavedFileName());
            $this->assertTrue(is_file($fileName));
            $this->assertEquals('YurunHttp Hello World', file_get_contents($fileName));
        });
    }

    /**
     * Download file auto extension.
     *
     * @return void
     */
    public function testDownloadAutoExt(): void
    {
        $this->call(function () {
            $http = new HttpRequest();
            $fileName = __DIR__ . '/download.html';
            if (is_file($fileName))
            {
                unlink($fileName);
            }
            $this->assertFalse(is_file($fileName));
            $response = $http->download(__DIR__ . '/download.*', $this->host . '?a=download2', 'yurunhttp=nb', 'POST');
            $this->assertEquals($fileName, $response->getSavedFileName());
            $this->assertTrue(is_file($fileName));
            $this->assertEquals('<h1>YurunHttp Hello World</h1>', file_get_contents($fileName));
        });
    }

    /**
     * Download file with redirect.
     *
     * @return void
     */
    public function testDownloadWithRedirect(): void
    {
        $this->call(function () {
            $http = new HttpRequest();
            $fileName = __DIR__ . '/download.html';
            if (is_file($fileName))
            {
                unlink($fileName);
            }
            $this->assertFalse(is_file($fileName));
            $response = $http->download(__DIR__ . '/download.*', $this->host . '?a=redirect&url=/?a=download3', 'yurunhttp=nb', 'POST');
            $this->assertEquals($fileName, $response->getSavedFileName());
            $this->assertTrue(is_file($fileName));
            $this->assertEquals('download3', file_get_contents($fileName));
        });
    }

    /**
     * Custom host.
     *
     * @return void
     */
    public function testCustomHost(): void
    {
        $this->call(function () {
            $http = new HttpRequest();
            $response = $http->header('host', 'www.imiphp.com')->get($this->host . '?a=info');
            $this->assertResponse($response);
            $data = $response->json(true);
            $this->assertEquals('www.imiphp.com', isset($data['header']['host']) ? $data['header']['host'] : null);
        });
    }

    /**
     * batch.
     *
     * @return void
     */
    public function testCoBatch(): void
    {
        $this->call(function () {
            $time = time();
            $fileName = __DIR__ . '/download.txt';
            if (is_file($fileName))
            {
                unlink($fileName);
            }
            $this->assertFalse(is_file($fileName));
            $fileName2Temp = __DIR__ . '/download2.*';
            $fileName2 = __DIR__ . '/download2.html';
            if (is_file($fileName2))
            {
                unlink($fileName2);
            }
            $this->assertFalse(is_file($fileName2));
            $fileName3Temp = __DIR__ . '/download3.*';
            $fileName3 = __DIR__ . '/download3.html';
            if (is_file($fileName3))
            {
                unlink($fileName3);
            }
            $this->assertFalse(is_file($fileName3));
            $result = Batch::run([
                (new HttpRequest())->url($this->host),
                (new HttpRequest())->url($this->host . '?a=info&time=' . $time),
                (new HttpRequest())->url($this->host . '?a=download1')->requestBody('yurunhttp=nb')->saveFile($fileName)->method('POST'),
                (new HttpRequest())->url($this->host . '?a=download1')->requestBody('yurunhttp=nb')->saveFile($fileName2Temp)->method('POST'),
                'a' => (new HttpRequest())->url($this->host . '?a=redirect&url=/?a=download3')->requestBody('yurunhttp=nb')->saveFile($fileName3Temp),
            ]);

            foreach ($result as $i => $response)
            {
                if (0 === $i)
                {
                    $this->assertResponse($response);
                    $this->assertEquals('YurunHttp', $response->body());
                    $this->assertNotNull($response->getRequest());
                }
                elseif (1 === $i)
                {
                    $data = $response->json(true);
                    $this->assertEquals('GET', isset($data['method']) ? $data['method'] : null);
                    $this->assertEquals($time, isset($data['get']['time']) ? $data['get']['time'] : null);
                    $this->assertNotNull($response->getRequest());
                }
                elseif (2 === $i)
                {
                    $this->assertTrue(is_file($fileName));
                    $this->assertEquals('YurunHttp Hello World', file_get_contents($fileName));
                    $this->assertNotNull($response->getRequest());
                    $this->assertEquals('text/html; charset=UTF-8', $response->getHeaderLine('Content-Type'));
                    $this->assertEquals('1', $response->getCookie('a'));
                }
                elseif (3 === $i)
                {
                    $this->assertTrue(is_file($fileName2));
                    $this->assertEquals('YurunHttp Hello World', file_get_contents($fileName2));
                    $this->assertNotNull($response->getRequest());
                    $this->assertEquals('text/html; charset=UTF-8', $response->getHeaderLine('Content-Type'));
                    $this->assertEquals('1', $response->getCookie('a'));
                }
                elseif ('a' === $i)
                {
                    $this->assertTrue(is_file($fileName3));
                    $this->assertEquals('download3', file_get_contents($fileName3));
                    $this->assertNotNull($response->getRequest());
                    $this->assertEquals('text/html; charset=UTF-8', $response->getHeaderLine('Content-Type'));
                }
                else
                {
                    throw new \RuntimeException(sprintf('Unknown %s', $i));
                }
            }
        });
    }

    /**
     * batch.
     *
     * @return void
     */
    public function testCoBatchTimeout(): void
    {
        $this->call(function () {
            $result = Batch::run([
                'gg' => (new HttpRequest())->url($this->host . '?a=sleep&time=3'),
            ], 1);
            $this->assertArrayHasKey('gg', $result);
            $this->assertFalse($result['gg']->success);
        });
    }

    public function testMemoryLeak(): void
    {
        $this->call(function () {
            $memorys = [1, 2, 3, 4, 5];
            for ($i = 0; $i < 5; ++$i)
            {
                $http = new HttpRequest();
                $http->get($this->host);
                $memorys[$i] = memory_get_usage();
            }
            unset($memorys[0]);
            $this->assertCount(1, array_unique($memorys));

            $memorys = [1, 2, 3, 4, 5];
            for ($i = 0; $i < 5; ++$i)
            {
                YurunHttp::send(new Request($this->host));
                $memorys[$i] = memory_get_usage();
            }
            unset($memorys[0]);
            $this->assertCount(1, array_unique($memorys));

            $memorys = [1, 2, 3, 4, 5];
            $time = time();
            for ($i = 0; $i < 5; ++$i)
            {
                Batch::run([
                    (new HttpRequest())->url($this->host),
                    (new HttpRequest())->url($this->host . '?a=info&time=' . $time),
                ]);
                $memorys[$i] = memory_get_usage();
            }
            unset($memorys[0]);
            $this->assertCount(1, array_unique($memorys));
        });
    }

    public function testHead(): void
    {
        $this->call(function () {
            $http = new HttpRequest();
            $response = $http->head($this->host . '?a=empty');
            $this->assertResponse($response);
        });
    }

    /**
     * @see https://github.com/Yurunsoft/YurunHttp/issues/19
     *
     * @return void
     */
    public function testBug19(): void
    {
        $this->call(function () {
            $http = new HttpRequest();
            $response = $http->head('https://www.baidu.com');
            $this->assertResponse($response);
            $response = $http->get('https://www.baidu.com');
            $this->assertResponse($response);
            $this->assertTrue('' != $response->body());
        });
    }

    /**
     * Cookie Jar.
     *
     * @return void
     */
    public function testCookieJar(): void
    {
        $this->call(function () {
            $http = new HttpRequest([
                HandlerOptions::COOKIE_JAR => __DIR__ . '/cookie.json',
            ]);

            $http->get($this->host . '?a=setCookie');

            $http = new HttpRequest([
                HandlerOptions::COOKIE_JAR => __DIR__ . '/cookie.json',
            ]);
            $response = $http->get($this->host . '?a=info');
            $data = $response->json(true);

            $this->assertEquals('1', isset($data['cookie']['a']) ? $data['cookie']['a'] : null);
            $this->assertEquals('3', isset($data['cookie']['c']) ? $data['cookie']['c'] : null);
        });
    }
}
