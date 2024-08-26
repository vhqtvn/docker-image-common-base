#!/bin/bash

BUILD_SLIM=1 php -d auto_prepend_file=includes/boot.php Dockerfile.php > Dockerfile
BUILD_SLIM=0 php -d auto_prepend_file=includes/boot.php Dockerfile.php > Dockerfile-full
