#!/bin/bash

__DIR__=$(cd `dirname $0`; pwd)

${__DIR__}/stop-server.sh

if [[ $TRAVIS ]]; then
phpPath="/opt/swoole/bin/php"
else
phpPath="/usr/bin/env php"
fi

nohup $phpPath $__DIR__/http2-server.php > /dev/null 2>&1 & echo $! > "$__DIR__/server.pid"
