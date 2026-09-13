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
    ];

    if (($restrict = env('IVFI_UPLOAD_RESTRICT')) !== null) {
        $config['upload']['restrict'] = $restrict;
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
