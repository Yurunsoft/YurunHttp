<?php
if(!function_exists('\go'))
{
    return; // not installed swoole
}

$cmd = 'cd ' . dirname(__DIR__) . '/tests/server/WebSocket/' . ' && composer update';
echo `{$cmd}`;
