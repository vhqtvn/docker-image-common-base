#!/bin/bash

docker rm --rm -v $(pwd)/utils/sync:/app/sync -w /app/sync alpine:3.16 sh -c "apk add g++; g++ -fPIC -shared sync.c -o sync.so; sync"
php -d auto_prepend_file=includes/boot.php Dockerfile.php > Dockerfile