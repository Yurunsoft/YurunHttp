#!/bin/bash

__DIR__=$(cd `dirname $0`; pwd)

cd $__DIR__

if [[ `expr $PHP_DOCKER_VERSION \< 7` -eq 0 ]]; then
  export PHP_DOCKER_FILE="php.dockerfile"
else
  export PHP_DOCKER_FILE="php-5.dockerfile"
fi

containerName=$1

docker-compose up -d $containerName \
&& docker exec $containerName php -v \
&& docker exec $containerName php -m \
&& docker exec $containerName composer -V \
&& docker ps -a

n=0
until [ $n -ge 5 ]
do
  docker exec $containerName composer update && break
  n=$[$n+1]
  sleep 1
done
