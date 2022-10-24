#!/usr/bin/env bash

PUSER=${LOCAL_USER_NAME:-user}

BASE_FILE="$1"
shift

HUID=`stat -c '%u' "${BASE_FILE}"`
HGID=`stat -c '%g' "${BASE_FILE}"`

echo "* Starting with UID=$HUID GID=$HGID" >&2

if [[ "$HUID" == "0" ]]; then
    echo "File owned by root, start as normal" >&2
    "$@"
else
    if ! id -u "${PUSER}" > /dev/null 2>&1; then
        echo "Adding user" >&2
        adduser -s /bin/bash -u $HUID -D "${PUSER}" 1>&2
        export HOME="/home/${PUSER}"
        echo '%wheel ALL=(ALL) NOPASSWD: ALL' > /etc/sudoers.d/wheel
        addgroup "${PUSER}" wheel
    fi

    su "${PUSER}" -c sh "$@"
fi
