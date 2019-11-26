<?php
require dirname(__DIR__) . '/vendor/autoload.php';

define('SWOOLE_ON', version_compare(PHP_VERSION, '7.0', '>='));

function testEnv($name, $default = null)
{
    $result = getenv($name);
    if(false === $result)
    {
        return $default;
    }
    return $result;
}

// Http Server
$cmd = __DIR__ . '/server/Http/start-server.sh';
echo 'Starting Http server...', PHP_EOL;
echo `{$cmd}`, PHP_EOL;
$serverStarted = false;
for($i = 0; $i < 10; ++$i)
{
    if('YurunHttp' === @file_get_contents(testEnv('HTTP_SERVER_HOST', 'http://127.0.0.1:8899/')))
    {
        $serverStarted = true;
        break;
    }
    sleep(1);
}
if($serverStarted)
{
    echo 'Http server started!', PHP_EOL;
}
else
{
    throw new \RuntimeException('Http server start failed');
}

if(SWOOLE_ON)
{
    // WebSocket Server
    $cmd = __DIR__ . '/server/WebSocket/start-server.sh';
    echo 'Starting WebSocket server...', PHP_EOL;
    echo `{$cmd}`, PHP_EOL;
    $serverStarted = false;
    for($i = 0; $i < 10; ++$i)
    {
        @file_get_contents(str_replace('ws://', 'http://', testEnv('WS_SERVER_HOST', 'ws://127.0.0.1:8900/')));
        if(isset($http_response_header[0]) && 'HTTP/1.1 400 Bad Request' === $http_response_header[0])
        {
            $serverStarted = true;
            break;
        }
        sleep(1);
    }
    if($serverStarted)
    {
        echo 'WebSocekt server started!', PHP_EOL;
    }
    else
    {
        throw new \RuntimeException('WebSocekt server start failed');
    }

    // Http2 Server
    $cmd = __DIR__ . '/server/Http2/start-server.sh';
    echo 'Starting Http2 server...', PHP_EOL;
    echo `{$cmd}`, PHP_EOL;
    $serverStarted = false;
    for($i = 0; $i < 10; ++$i)
    {
        @file_get_contents(testEnv('HTTP2_SERVER_HOST', 'http://127.0.0.1:8901/'));
        if(isset($http_response_header[0]) && 'HTTP/1.1 200 OK' === $http_response_header[0])
        {
            $serverStarted = true;
            break;
        }
        sleep(1);
    }
    if($serverStarted)
    {
        echo 'Http2 server started!', PHP_EOL;
    }
    else
    {
        throw new \RuntimeException('Http2 server start failed');
    }
}

register_shutdown_function(function(){
    // stop server
    $cmd = __DIR__ . '/server/Http/stop-server.sh';
    echo 'Stoping http server...', PHP_EOL;
    echo `{$cmd}`, PHP_EOL;
    echo 'Http Server stoped!', PHP_EOL;

    if(SWOOLE_ON)
    {
        $cmd = __DIR__ . '/server/WebSocket/stop-server.sh';
        echo 'Stoping WebSocket server...', PHP_EOL;
        echo `{$cmd}`, PHP_EOL;
        echo 'WebSocket Server stoped!', PHP_EOL;

        $cmd = __DIR__ . '/server/Http2/stop-server.sh';
        echo 'Stoping Http2 server...', PHP_EOL;
        echo `{$cmd}`, PHP_EOL;
        echo 'Http2 Server stoped!', PHP_EOL;
    }
});