<?php

$dir = dirname(__DIR__) . '/tests/server/';

foreach ([
    $dir . 'Http/log.log',
    $dir . 'Http2/log.log',
    $dir . 'WebSocket/log.log',
] as $fileName)
{
    echo '[',$fileName,']', \PHP_EOL;
    if (is_file($fileName))
    {
        echo file_get_contents($fileName), \PHP_EOL;
    }
}
