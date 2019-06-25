<?php
require __DIR__ . '/Args.php';

Args::init();
$nproc = Args::get('nproc');
$versionName = Args::get('version-name');

define('OTHER_SWOOLE_VERSION', 'v4.4.0-beta');
define('PHP_70_SWOOLE_VERSION', 'v4.3.5');
define('PHP_73_SWOOLE_VERSION', 'ed77b8b48fd113dad9bc51dc348a51aec51b220d');

if(version_compare(PHP_VERSION, '7.0', '>='))
{
    $version = OTHER_SWOOLE_VERSION;
    if(version_compare(PHP_VERSION, '7.1', '<'))
    {
        $version = PHP_70_SWOOLE_VERSION;
    }
    else if(version_compare(PHP_VERSION, '7.3', '>=') && version_compare(PHP_VERSION, '7.4', '<'))
    {
        $version = PHP_73_SWOOLE_VERSION;
    }
    `wget https://github.com/swoole/swoole-src/archive/{$version}.tar.gz -O swoole.tar.gz && mkdir -p swoole && tar -xf swoole.tar.gz -C swoole --strip-components=1 && rm swoole.tar.gz && cd swoole && phpize && ./configure && make -j{$nproc} && make install && cd -`;

    `echo "extension = swoole.so" >> ~/.phpenv/versions/{$versionName}/etc/php.ini`;
}
else
{
    echo 'Skip Swoole', PHP_EOL;
}
