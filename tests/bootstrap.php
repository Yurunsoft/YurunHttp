<?php
require dirname(__DIR__) . '/vendor/autoload.php';

// start server
$cmd = __DIR__ . '/server/start-server.sh';
echo 'Starting test server...', PHP_EOL;
echo `{$cmd}`, PHP_EOL;
$serverStarted = false;
for($i = 0; $i < 10; ++$i)
{
    if('YurunHttp' === @file_get_contents('http://127.0.0.1:8899/'))
    {
        $serverStarted = true;
        break;
    }
    sleep(1);
}
if($serverStarted)
{
    echo 'Test server started!', PHP_EOL;
}
else
{
    throw new \RuntimeException('Test server start failed');
}
register_shutdown_function(function(){
    // stop server
    $cmd = __DIR__ . '/server/stop-server.sh';
    echo 'Stoping test server...', PHP_EOL;
    echo `{$cmd}`, PHP_EOL;
    echo 'Server stoped!', PHP_EOL;
});