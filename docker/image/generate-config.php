<?php

/**
 * Writes `indexer.config.php` from the container's environment.
 *
 * Runs once at start, so php-fpm never needs the environment itself and a
 * plaintext password is hashed before it reaches a file.
 *
 * A PHP file mounted at /etc/ivfi/config.php is merged over the result, for
 * anything the variables below do not cover.
 */

function env($name, $default = null)
{
    $value = getenv($name);

    return ($value === false || $value === '') ? $default : $value;
}

function flag($name, $default)
{
    $value = env($name);

    if ($value === null) {
        return $default;
    }

    return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
}

$config = [];

$username = env('IVFI_USERNAME');
$password = env('IVFI_PASSWORD');
$hash = env('IVFI_PASSWORD_HASH');

if ($username !== null && ($password !== null || $hash !== null)) {
    $config['authentication'] = [
        'users' => [
            $username => $hash !== null
                ? $hash
                : password_hash($password, PASSWORD_DEFAULT),
        ],
        'behind_proxy' => flag('IVFI_BEHIND_PROXY', true),
        'client_ip_header' => env('IVFI_CLIENT_IP_HEADER', 'X-Forwarded-For'),
        'throttle_path' => '/var/lib/ivfi',
    ];

    if (($restrict = env('IVFI_AUTH_RESTRICT')) !== null) {
        $config['authentication']['restrict'] = $restrict;
    }

    $extensions = env('IVFI_UPLOAD_EXTENSIONS');

    $config['upload'] = [
        'enabled' => flag('IVFI_UPLOAD', true),
        'extensions' => $extensions === null
            ? true
            : array_values(array_filter(array_map('trim', explode(',', $extensions)))),
        'overwrite' => flag('IVFI_UPLOAD_OVERWRITE', false),
        'directories' => flag('IVFI_UPLOAD_DIRECTORIES', true),
        'delete' => flag('IVFI_UPLOAD_DELETE', true),
        /* nginx.conf sandboxes .html from /data, so listing it is safe here */
        'sandboxed_html' => true,
    ];

    if (($restrict = env('IVFI_UPLOAD_RESTRICT')) !== null) {
        $config['upload']['restrict'] = $restrict;
    }

    /**
     * Read-only share links, which only mean anything once there is a password
     * to hand someone a way round.
     *
     * The tokens are left in their default place, a dotted directory at the
     * base of /data: that is the volume, so they survive a redeploy, while
     * /var/lib/ivfi is in the container's writable layer and does not.
     */
    $config['share'] = [
        'enabled' => flag('IVFI_SHARE', false),
    ];

    if (($expiry = env('IVFI_SHARE_EXPIRY')) !== null) {
        $config['share']['expiry'] = (int) $expiry;
    }

    if (($sharePath = env('IVFI_SHARE_PATH')) !== null) {
        $config['share']['path'] = $sharePath;
    }
} else {
    fwrite(STDERR, "ivfi: IVFI_USERNAME and IVFI_PASSWORD are not both set; "
        . "the index is public and uploads are off\n");
}

if (is_file('/etc/ivfi/config.php')) {
    $extra = require '/etc/ivfi/config.php';

    if (is_array($extra)) {
        $config = array_replace_recursive($config, $extra);
    }
}

echo "<?php\n/* Generated at container start by generate-config.php */\nreturn "
    . var_export($config, true) . ";\n";
