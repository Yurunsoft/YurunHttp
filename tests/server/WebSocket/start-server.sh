#!/bin/bash

__DIR__=$(cd `dirname $0`; pwd)

${__DIR__}/stop-server.sh

nohup /usr/bin/env php $__DIR__/ws-server.php > /dev/null 2>&1 & echo $! > "$__DIR__/server.pid"
