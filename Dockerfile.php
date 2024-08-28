#<?php dockerfile_include_dir("php", "php/8.3/alpine3.20/cli"); ?>
#<?php if(!getenv('BUILD_SLIM')) dockerfile_include_dir("pandoc", "pandoc/alpine", "pandoc", ["arg_replace"=>["base_image_version"=>"3.20"]]); ?>
#<?php dockerfile_include_dir("composer", "composer/lts", null, ["ignore"=>fn($line)=>strpos($line,"docker-entrypoint")!==false]); ?>
#<?php dockerfile_include_dir("node", "docker-node/20/alpine3.20"); ?>

################################################################################
############# SECTION MAIN
################################################################################

FROM alpine:3.20 AS vhmain-builder

#<?php if(!getenv('BUILD_SLIM')): ?>

COPY --from=pandoc--alpine-core \
  /usr/local/bin/pandoc \
  /usr/local/bin/pandoc-crossref \
  /usr/local/bin/

#<?php endif; ?>


RUN apk --no-cache add \
        gmp \
        libffi \
        librsvg \
        lua$lua_version \
        lua$lua_version-lpeg

#<?php docker_file_flush_pending(); ?>

RUN apk add --no-cache sudo

RUN set -eux; \
	apk add libxml2-dev \
		libzip-dev \
	; \
    docker-php-ext-install xml xmlwriter zip

RUN deluser --remove-home node

# docker run --rm -v $(pwd)/utils/sync:/app/sync -w /app/sync alpine:3.20 sh -c "apk add g++; g++ -fPIC -shared sync.c -o sync.so; sync"
FROM alpine:3.20 AS sync-builder

COPY ./utils/sync/sync.c /app/sync/

RUN apk add --no-cache g++ && \
    g++ -fPIC -shared /app/sync/sync.c -o /app/sync.so

FROM alpine:3.20 AS main

COPY --from=vhmain-builder / /

WORKDIR /app

RUN apk add --no-cache \
        bash

COPY ./bin/wrap-uid.sh /usr/bin/
COPY --from=sync-builder /app/sync.so /vh/lib/

CMD ["bash"]
