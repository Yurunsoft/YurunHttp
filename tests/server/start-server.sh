#!/bin/bash

__DIR__=$(cd `dirname $0`; pwd)

${__DIR__}/stop-server.sh

nohup /usr/bin/env php -t "$__DIR__" -S 127.0.0.1:8899 "$__DIR__/server.php" > /dev/null 2>&1 & echo $! > "$__DIR__/server.pid"
