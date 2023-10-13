#!/bin/bash

__DIR__=$(cd `dirname $0`; pwd)

${__DIR__}/stop-server.sh

/usr/bin/env php $__DIR__/server.php start -d  > log.log