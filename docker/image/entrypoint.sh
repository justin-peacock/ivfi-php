#!/bin/sh
set -eu

size="${IVFI_MAX_UPLOAD_SIZE:-100M}"

case "$size" in
	*[!0-9KMGkmg]* | '' )
		echo "IVFI_MAX_UPLOAD_SIZE must look like 100M or 2G, got: $size" >&2
		exit 1
		;;
esac

# Same limit for nginx and PHP, so neither turns away what the other accepts
echo "client_max_body_size $size;" > /etc/nginx/ivfi-limits.conf
printf '[www]\nphp_admin_value[upload_max_filesize] = %s\nphp_admin_value[post_max_size] = %s\n' \
	"$size" "$size" > /usr/local/etc/php-fpm.d/zz-ivfi-limits.conf

# A fresh volume is owned by root; uploads are written by php-fpm's user
if [ "$(stat -c %U /data)" != "www-data" ]; then
	chown www-data:www-data /data
fi

php /usr/local/lib/ivfi/generate-config.php > /var/www/html/indexer.config.php
chown root:www-data /var/www/html/indexer.config.php
chmod 640 /var/www/html/indexer.config.php

php-fpm --daemonize
exec nginx -g 'daemon off;'
