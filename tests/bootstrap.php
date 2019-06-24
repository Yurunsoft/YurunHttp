<?php
require dirname(__DIR__) . '/vendor/autoload.php';

// start server
$cmd = __DIR__ . '/server/start-server.sh';
echo `{$cmd}`, PHP_EOL;

register_shutdown_function(function(){
    // stop server
    $cmd = __DIR__ . '/server/stop-server.sh';
    echo `{$cmd}`, PHP_EOL;
});