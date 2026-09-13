#!/bin/sh
set -eu

size="${IVFI_MAX_UPLOAD_SIZE:-100M}"

# Digits, then at most one unit. Anything else is a typo worth failing on
digits="${size%[KMGkmg]}"

case "$digits" in
	'' | *[!0-9]* )
		echo "IVFI_MAX_UPLOAD_SIZE must look like 100M or 2G, got: $size" >&2
		exit 1
		;;
esac

if [ "$digits" -eq 0 ]; then
	echo "IVFI_MAX_UPLOAD_SIZE must be greater than zero, got: $size" >&2
	exit 1
fi

case "${size#"$digits"}" in
	K|k ) bytes=$(( digits * 1024 )) ;;
	M|m ) bytes=$(( digits * 1024 * 1024 )) ;;
	G|g ) bytes=$(( digits * 1024 * 1024 * 1024 )) ;;
	*   ) bytes="$digits" ;;
esac

# The request carries multipart boundaries and the other form fields as well as
# the file, so the body limits sit above the per-file one. Without the headroom
# a file of exactly IVFI_MAX_UPLOAD_SIZE is refused: the indexer advertises
# `post_max_size` minus its envelope allowance as the ceiling, and nginx counts
# the whole body against client_max_body_size
body=$(( bytes + 1048576 ))

echo "client_max_body_size ${body};" > /etc/nginx/ivfi-limits.conf
printf '[www]\nphp_admin_value[upload_max_filesize] = %s\nphp_admin_value[post_max_size] = %s\n' \
	"$bytes" "$body" > /usr/local/etc/php-fpm.d/zz-ivfi-limits.conf

# A fresh volume is owned by root; uploads are written by php-fpm's user
if [ "$(stat -c %U /data)" != "www-data" ]; then
	chown www-data:www-data /data
fi

php /usr/local/lib/ivfi/generate-config.php > /var/www/html/indexer.config.php
chown root:www-data /var/www/html/indexer.config.php
chmod 640 /var/www/html/indexer.config.php

php-fpm --daemonize
exec nginx -g 'daemon off;'
