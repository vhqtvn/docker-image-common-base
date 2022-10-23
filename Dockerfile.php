#<?php dockerfile_include_dir("php", "php/8.1/alpine3.16/cli"); ?>
#<?php dockerfile_include_dir("pandoc", "pandoc/alpine", "pandoc"); ?>
#<?php dockerfile_include_dir("composer", "composer/2.4", null, ["ignore"=>fn($line)=>strpos($line,"docker-entrypoint")!==false]); ?>

################################################################################
############# SECTION MAIN
################################################################################

FROM alpine:3.16

COPY --from=alpine-core \
  /usr/local/bin/pandoc \
  /usr/local/bin/pandoc-crossref \
  /usr/local/bin/

RUN apk --no-cache add \
        gmp \
        libffi \
        librsvg \
        lua$lua_version \
        lua$lua_version-lpeg

#<?php docker_file_flush_pending(); ?>

RUN set -eux; \
	apk add --no-cache --virtual .build-deps \
		libxml2-dev \
		libzip-dev \
	; \
    docker-php-ext-install xml xmlwriter zip && \
    apk del --no-network .build-deps


WORKDIR /app
