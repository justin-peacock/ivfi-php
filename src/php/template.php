<?php
/**
 * <ivfi-php> [https://github.com/sixem/ivfi-php]
 *
 * @license  https://github.com/sixem/ivfi-php/blob/master/LICENSE GPL-3.0
 * @author   emy (sixem@github) <emy@five.sh>
 * @version  <%= version %>
 */

/**
 * [Configuration]
 * A more in-depth overview can be found here:
 * https://git.five.sh/ivfi/docs/php/#/config
 */

/* Used to bust the cache and to display footer version number */
$version = '<%= version %>';

$config = [
    /**
     * Authentication options
     *
     * Sign-in is by session cookie against a login form. Credentials are
     * `password_hash()` values, never plaintext; generate one with:
     *
     *   php -r 'echo password_hash("your password", PASSWORD_DEFAULT), "\n";'
     *
     *   'authentication' => [
     *     'users' => ['emy' => '$2y$12$...'],
     *     'restrict' => '/^\/(private)\/?/i',  // optional, gate only these paths
     *     'behind_proxy' => true,               // trust X-Forwarded-Proto for the Secure flag
     *     'throttle_path' => '/var/lib/ivfi'    // where failed attempts are counted
     *   ]
     */
    'authentication' => false,
    /**
     * Enables single-page features
     */
    'single_page' => false,
    /**
     * Formatting options
     */
    'format' => [
        'title' => 'Index of %s', /* Title format where %s is the current path */
        'date' => ['d/m/y H:i', 'd/m/y'], /* Date formats (desktop, mobile) */
        'sizes' => [' B', ' KiB', ' MiB', ' GiB', ' TiB'] /* Size formats */
    ],
    /**
     * Favicon options
     */
    'icon' => [
        'path' => '/favicon.ico', /* What favicon to use */
        'mime' => 'image/x-icon' /* Favicon mime type */
    ],
    /**
     * Sorting options.
     * 
     * Used as default until the client sets their own sorting settings
     */
    'sorting' => [
        'enabled' => false, /* Whether the server should sort the items */
        'order' => SORT_ASC, /* Sorting order. asc or desc */
        'types' => 0, /* What item types to sort. 0 = both. 1 = files only. 2 = directories only */
        'sort_by' => 'name', /* What to sort by. available options are name, modified, type and size */
        'use_mbstring' => false /* Enabled mbstring when sorting */
    ],
    /**
     * Gallery options
     */
    'gallery' => [
        'enabled' => true, /* Whether the gallery plugin should be enabled */
        'reverse_options' => false, /* Reverse search options for images (when hovering over them) */
        'scroll_interval' => 50, /* Break in ms between scroll navigation events */
        'list_alignment' => 0, /* List alignment where 0 is right and 1 is left */
        'fit_content' => true, /* Whether the media should be forced to fill the screen space */
        'image_sharpen' => false, /* Attempts to disable browser blurriness on images */
    ],
    /**
     * Preview options
     */
    'preview' => [
        'enabled' => true, /* Whether the preview plugin should be enabled */
        'hover_delay' => 75, /* Delay in milliseconds before the preview is shown */
        'cursor_indicator' => true /* Displays a loading cursor while the preview is loading */
    ],
    /**
     * Extension that should be marked as media.
     * These extensions will have potential previews and will be included in the gallery
     */
    'extensions' => [
        'image' => ['jpg', 'jpeg', 'png', 'gif', 'ico', 'svg', 'bmp', 'webp'],
        'video' => ['webm', 'mp4', 'ogg', 'ogv', 'mov']
    ],
    /**
     * Injection options
     */
    'inject' => false,
    /**
     * Styling options
     */
    'style' => [
        /* Set to a path relative to the root directory (location of this file) containg .css files.
         * Each .css file will be treated as a separate theme. Set to false to disable themes */
        'themes' => [
          'path' => '/<%= indexerPath %>/themes/',
          'default' => false
        ],
         /* Cascading style sheets options */
        'css' => [
          'additional' => false
        ],
        /* Enables a more compact styling of the page */
        'compact' => false
    ],
    /**
     * Filter what files or directories to show.

     * Uses regular expressions. All names *matching* the regex will be shown.
     * Setting the value to false will disable the respective filter
     */
    'filter' => [
        'file' => false,
        'directory' => false
    ],
    /** Extensions to exclude */
    'exclude' => false,
    /**
     * Calculates the size of directories.

     * This can be intensive, especially with the recursive
     * option, so be aware of that
     */
    'directory_sizes' => [
      /* Whether directory sizes should be calculated or not */
      'enabled' => false,
      /* Recursively scans the directories when calculating the size */
      'recursive' => false
    ],
    /* Metadata options */
    'metadata' => false,
    /* Processing functions */
    'processor' => false,
    /* Should ? and # characters be encoded when processing URLs */
    'encode_all' => false,
    /* Whether this .php file should be directly accessible */
    'allow_direct_access' => false,
    /* Set to 'strict' or 'weak'.
     * 'strict' uses realpath() to avoid backwards directory traversal
     * whereas 'weak' uses a similar string-based approach */
    'path_checking' => 'strict',
    /**
     * Whether the `X-Indexer-Prepend-Path` request header is honoured.
     *
     * The header rewrites every link the page emits, so it must only be
     * trusted where a reverse proxy sets it and strips any copy sent by the
     * client. Leave this disabled unless that is your setup, and prefer the
     * `INDEXER_PREPEND_PATH` server variable, which a client cannot reach.
     */
    'trust_prepend_header' => false,
    /* Enabled the performance mode */
    'performance' => false,
    /* Whether extra information in the footer should be generated */
    'footer' => [
      'enabled' => true,
      'show_server_name' => true
    ],
    /**
     * Displays a simple link to the git repository in the
     * footer along with the current version.
     * 
     * I would really appreciate it if you would keep this enabled
     */
    'credits' => true,
    /**
     * Enables console output in JS and PHP debugging.
     * Also enables random query-strings for js/css files to bust the cache.
     *
     * Keep this disabled in production: it exposes exception traces, which
     * carry absolute filesystem paths, and it defeats caching of the assets
     */
    'debug' => false
];

/* Any potential libraries and so on for extra features will appear here */
<%= buildInject.readmeSupport &&
  buildInject.readmeSupport.PARSEDOWN_LIBRARY ?
  buildInject.readmeSupport.PARSEDOWN_LIBRARY : null %>

/* Define current request URI */
define('CURRENT_URI', rawurldecode($_SERVER['REQUEST_URI']));
/* Define default configuration file */
define('CONFIG_FILE', basename(__FILE__, '.php') . '.config.php');
/* Define default dotfile name */
define('DOTFILE_NAME', '.ivfi');
/** Define script identifier */
define('SCRIPT_ID', '__IVFI_DATA__');
/* Define the base path of the Indexer */
define('BASE_PATH', isset($_SERVER['INDEXER_BASE_PATH'])
  ? $_SERVER['INDEXER_BASE_PATH']
  : dirname(__FILE__));

/* Check if cookie is set */
$client = isset($_COOKIE['IVFi']) ? $_COOKIE['IVFi'] : NULL;

/** Define the current theme */
$currentTheme = NULL;

/* If client cookie is set, then parse it using `json_decode()` */
if($client)
{
  $client = json_decode($client, true);
}

/* Validate that the cookie is a valid array type */
$validate = is_array($client);

/** Define compact mode */
$compact = NULL;

/* Passed to any inject functions that are called from config */
$injectPassableData = [];

/* Set any additional CSS */
$additionalCss = "<%= additonalCss ? additonalCss.join('') : null %>";

/** Define themes array */
$themes = [
  'default' => [
    'path' => NULL
  ]
];

/**
 * Helper functions for the Indexer
 */ 
class Helpers
{
  /**
   * Checks if a string starts with a string
   *
   * @param String  $haystack  The string to match against
   * @param String  $needle    The string needle
   * 
   * @return Boolean
   */ 
  public static function startsWith($haystack, $needle)
  {
    return $needle === '' || strncmp($haystack, $needle, strlen($needle)) === 0;
  }

  /**
   * Encodes a value for safe inclusion in HTML text or an attribute value
   *
   * This is the single escaping primitive for the script. Every value that
   * reaches the document should pass through here unless it is known-safe
   * markup that was itself assembled by `createElement()`.
   *
   * @param Mixed  $value  The value to encode
   *
   * @return String
   */
  public static function escape($value)
  {
    return htmlspecialchars(
      (string) $value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8'
    );
  }

  /**
   * Creates a stringed HTML element
   *
   * Attribute values are always encoded, and attribute names that could
   * break out of the tag are dropped. Inner text is encoded by default;
   * callers that pass markup assembled by `createElement()` must opt out
   * using $rawText.
   *
   * `script` and `style` hold raw character data, so their contents are
   * never HTML-encoded (that would corrupt the CSS or JS). The closing
   * sequence is neutralised instead.
   *
   * @param String   $tag          Element type
   * @param Array    $attributes   Element attributes
   * @param String   $text         Inner text
   * @param Boolean  $rawText      Whether $text is already-safe markup
   *
   * @return String
   */
  public static function createElement($tag, $attributes, $text = NULL, $rawText = false)
  {
    /** Avoid using closing tags for these element types */
    $useClosing = !in_array($tag, [
      'link', 'meta'
    ]);

    /** These elements hold raw character data rather than parsed HTML */
    $isRawTextElement = in_array(strtolower($tag), [
      'script', 'style'
    ]);

    $HTML = ('<' . $tag);

    foreach($attributes as $key => $value)
    {
      /**
       * Drop any attribute name that isn't a plain HTML attribute name,
       * so a crafted key can't inject additional attributes or close the tag
       */
      if(!preg_match('/^[A-Za-z_:][A-Za-z0-9_:.\-]*$/', $key))
      {
        continue;
      }

      /**
       * A NULL or empty value renders as a bare attribute. Note this is a
       * strict check: a loose one would also swallow the string "0", which
       * turned valid values such as `data-raw="0"` into bare attributes
       */
      $HTML .= ($value === NULL || $value === '')
        ? (' ' . $key)
        : (' ' . $key . '="' . self::escape($value) . '"');
    }

    if($text === NULL || $text === '')
    {
      $content = '';
    } else if($isRawTextElement)
    {
      $content = str_ireplace(
        ['</script', '</style'], ['<\/script', '<\/style'], $text
      );
    } else if($rawText)
    {
      $content = $text;
    } else {
      $content = self::escape($text);
    }

    $HTML .= $useClosing
      ? ('>' . $content . '</' . $tag . '>')
      : ($content . '>');

    return $HTML;
  }

  /**
   * A realpath alternative that solves links by using
   * a string-based approach instead
   *
   * @param String  $input  A path
   * 
   * @return String
   */ 
  private static function removeDotSegments($input)
  {
    $output = '';

    while($input !== '')
    {
      if(($prefix = substr($input, 0, 3)) == '../'
        || ($prefix = substr($input, 0, 2)) == './')
      {
        $input = substr($input, strlen($prefix));
      } else if(($prefix = substr($input, 0, 3)) == '/./'
        || ($prefix = $input) == '/.')
      {
        $input = '/' . substr($input, strlen($prefix));
      } else if (($prefix = substr($input, 0, 4)) == '/../'
        || ($prefix = $input) == '/..')
      {
        $input = '/' . substr($input, strlen($prefix));
        $output = substr($output, 0, strrpos($output, '/'));
      } else if($input == '.' || $input == '..')
      {
        $input = '';
      } else {
        $pos = strpos($input, '/');
        if($pos === 0) $pos = strpos($input, '/', $pos+1);
        if($pos === false) $pos = strlen($input);
        $output .= substr($input, 0, $pos);
        $input = (string) substr($input, $pos);
      }
    }

    return $output;
  }

  /**
   * Concentrates path components into a merged path
   *
   * @param String  ...$params   Path components
   * 
   * @return String
   */ 
  public static function joinPaths(...$params)
  {
    $paths = [];

    foreach($params as $param)
    {
      if($param !== '')
      {
        $paths[] = $param;
      }
    }

    return preg_replace('#/+#','/', join(DIRECTORY_SEPARATOR, $paths));
  }

  /**
   * Checks if the passed path is above a base directory
   * 
   * $useRealpath resolves the paths using a string-based method
   * as opposed to calling `realpath()` directly.
   *
   * @param String   $path          The path to check
   * @param String   $base          The base path
   * @param Boolean  $useRealpath   Whether to use realpath
   *
   * @return Boolean
   */
  public static function isAboveCurrent($path, $base, $useRealpath = true)
  {
    if($useRealpath)
    {
      $path = realpath($path);
      $base = realpath($base);
    } else {
      $path = self::removeDotSegments($path);
      $base = self::removeDotSegments($base);
    }

    /* `realpath()` returns false for anything it cannot resolve */
    if($path === false || $base === false)
    {
      return false;
    }

    /* The base directory itself is inside the base directory */
    if($path === $base)
    {
      return true;
    }

    /**
     * Compare with a trailing separator on both sides so the match lands on a
     * path boundary. A plain prefix test accepts any sibling whose name merely
     * begins with the base, so a base of `/srv/pub` would wrongly be treated
     * as containing `/srv/pub-secret`
     */
    return self::startsWith(
      rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR,
      rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR
    );
  }

  /**
   * Adds a character to both sides of a string
   * 
   * If the string already ends or starts with the given
   * string, it will be ignored.
   *
   * @param String  $string   String to wrap around
   * @param String  $char     Character to prepend and append
   * 
   * @return String
   */ 
  public static function stringWrap($string, $char)
  {
    if($string[0] !== $char)
    {
      $string = ($char . $string);
    }
  
    if(substr($string, -1) !== $char)
    {
      $string = ($string . $char);
    }

    return $string;
  }

  /**
   * Reads a JSON file and returns the data
   *
   * @param String  $filePath   Path of the JSON file
   * 
   * @return String
   */ 
  public function readJson($filePath)
  {
    if(!file_exists($filePath))
    {
      return false;
    }

    $json = file_get_contents($filePath);

    if(!$json)
    {
      return false;
    }

    $data = json_decode($json, true);

    if(json_last_error() !== JSON_ERROR_NONE)
    {
      if($this->debug)
      {
        echo json_last_error_msg();
      }
      
      return false;
    }

    return $data;
  }

  /**
   * Merges two sets of metadata arrays
   *
   * @param Array  $source   Source array
   * @param Array  $data     Priority array
   * 
   * @return String
   */ 
  public static function mergeMetadata(array $source, array $data)
  {
    $metadata = [];

    /** Iterate over and store current metadata */
    foreach($source as $item)
    {
        foreach($item as $key => $value)
        {
            if($key !== 'content') {
                /** Reset object if no content is present, or create on unexisting key */
                if((isset($item['content']) && $item['content'] === false)
                  || !array_key_exists($key, $metadata))
                {
                  $metadata[$key] = [];
                }

                $metadata[$key][$value] = $item['content'] ?? false;
            }
        }
    }

    /** Iterate over new metadata, overwrite when needed */
    foreach($data as $item)
    {
        $content = $item['content'] ?? false;

        foreach($item as $key => $value)
        {
            if($key !== 'content')
            {
                /** Reset object if no content is present, or create on unexisting key */
                if($content === false || !array_key_exists($key, $metadata))
                {
                  $metadata[$key] = [];
                }

                $metadata[$key][$value] = $content;
            }
        }
    }

    /** Create and return metadata array */
    $result = [];

    foreach($metadata as $property => $values)
    {
        foreach($values as $key => $content)
        {
            $item = [$property => $key];

            if($content)
            {
              $item['content'] = $content;
            }

            $result[] = $item;
        }
    }

    return $result;
  }
}

/** Name of the session cookie */
define('AUTH_SESSION_NAME', 'IVFISESS');
/** Failed attempts from one address before it is locked out */
define('AUTH_MAX_ATTEMPTS', 5);
/** How long a lockout lasts, in seconds */
define('AUTH_LOCKOUT_SECONDS', 900);
/** How long a session may sit idle before it stops being accepted, in seconds */
define('AUTH_IDLE_TIMEOUT', 43200);
/** Field names used by the login form */
define('AUTH_FIELD_USER', 'ivfi_user');
define('AUTH_FIELD_PASS', 'ivfi_pass');
define('AUTH_FIELD_CSRF', 'ivfi_csrf');
/** Query parameter that signs the client out */
define('AUTH_PARAM_LOGOUT', 'ivfi_logout');

/**
 * Whether the request reached us over HTTPS
 *
 * Used to decide whether the session cookie carries the `Secure` flag, so
 * getting it wrong either leaks the cookie over plaintext or makes login
 * silently impossible.
 *
 * @param Boolean  $trustProxy  Whether a forwarding proxy sits in front
 *
 * @return Boolean
 */
function authIsSecureRequest($trustProxy)
{
  if(!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
  {
    return true;
  }

  if(!empty($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443)
  {
    return true;
  }

  /**
   * Only believe the forwarded header when the operator has said a proxy is
   * in front. Any client can send it, so trusting it unconditionally would
   * let a visitor decide whether their own cookie is protected
   */
  if($trustProxy && isset($_SERVER['HTTP_X_FORWARDED_PROTO']))
  {
    return strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https';
  }

  return false;
}

/**
 * Path of the file that records failed attempts
 *
 * @param Array  $options  Authentication options
 *
 * @return String
 */
function authThrottlePath($options)
{
  $dir = (isset($options['throttle_path']) && $options['throttle_path'])
    ? $options['throttle_path']
    : sys_get_temp_dir();

  return rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'ivfi-auth.json';
}

/**
 * Identifies the source of an attempt without storing who made it
 *
 * Keyed on the address alone, deliberately. Including the username would let
 * one address try a common password against a list of names without ever
 * tripping a lockout, since each name would carry its own count.
 *
 * @return String
 */
function authThrottleKey()
{
  $address = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '-';

  return hash('sha256', $address);
}

/**
 * Reads, updates and prunes the failed attempt record
 *
 * Kept in one function so the file is opened, locked, changed and closed in a
 * single place. Attempts are tracked server-side because anything held in the
 * session would be discarded by the client along with the cookie.
 *
 * @param Array   $options   Authentication options
 * @param String  $action    One of 'check', 'fail' or 'clear'
 *
 * @return Integer  Seconds remaining on a lockout, or 0
 */
function authThrottle($options, $action)
{
  $path = authThrottlePath($options);
  $key = authThrottleKey();
  $now = time();

  $handle = @fopen($path, 'c+');

  if($handle === false)
  {
    /**
     * Without somewhere to record attempts there is no throttling. Say so
     * rather than failing the request, but make it visible: an unthrottled
     * login is a real weakening and should not pass unnoticed
     */
    error_log(sprintf(
      'IVFi: cannot write the login throttle file at %s, attempts are not being limited', $path
    ));

    return 0;
  }

  flock($handle, LOCK_EX);

  $size = filesize($path);
  $raw = $size > 0 ? fread($handle, $size) : '';
  $records = json_decode((string) $raw, true);

  if(!is_array($records))
  {
    $records = [];
  }

  /* Drop anything old enough to no longer matter */
  foreach($records as $recorded => $entry)
  {
    if(!isset($entry['seen']) || ($now - $entry['seen']) > AUTH_LOCKOUT_SECONDS)
    {
      unset($records[$recorded]);
    }
  }

  $remaining = 0;

  if($action === 'clear')
  {
    unset($records[$key]);
  } else {
    $count = isset($records[$key]['count']) ? (int) $records[$key]['count'] : 0;
    $seen = isset($records[$key]['seen']) ? (int) $records[$key]['seen'] : $now;

    if($action === 'fail')
    {
      $count++;
      $seen = $now;

      $records[$key] = ['count' => $count, 'seen' => $seen];
    }

    if($count >= AUTH_MAX_ATTEMPTS)
    {
      $remaining = max(0, (AUTH_LOCKOUT_SECONDS - ($now - $seen)));
    }
  }

  ftruncate($handle, 0);
  rewind($handle);
  fwrite($handle, json_encode($records));
  fflush($handle);
  flock($handle, LOCK_UN);
  fclose($handle);

  return $remaining;
}

/**
 * The current request target, reduced to a path this site can serve
 *
 * Never build a `Location` header out of `REQUEST_URI` directly. It is the
 * request line as the client wrote it, so `//evil.example/` arrives intact and
 * is a protocol relative URL: sending it back redirects the visitor off-site.
 * On a sign-in flow that is a ready-made phishing hop, since the victim
 * authenticates on the real domain and lands somewhere else.
 *
 * @param Boolean  $keepQuery  Whether to carry the query string over
 *
 * @return String
 */
function authLocalTarget($keepQuery = true)
{
  $uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '/';

  /* Drops any scheme and authority an absolute-form request line carries */
  $parts = parse_url($uri);

  if($parts === false)
  {
    return '/';
  }

  $path = (isset($parts['path']) && $parts['path'] !== '') ? $parts['path'] : '/';

  /**
   * Collapse leading slashes and backslashes, so neither `//host` nor the
   * `/\host` that some clients normalise to it can survive
   */
  $path = '/' . ltrim($path, "/\\");

  if($keepQuery && isset($parts['query']) && $parts['query'] !== '')
  {
    $path .= '?' . $parts['query'];
  }

  return $path;
}
/**
 * Renders the login page and stops
 *
 * Self contained rather than styled by the built stylesheet, because it is
 * shown before anything about the listing is known and should not depend on
 * assets that a misconfigured path could fail to serve.
 *
 * @param String   $error   Message to show, if any
 * @param Integer  $status  HTTP status to send
 *
 * @return Void
 */
function authRenderLogin($error = '', $status = 401)
{
  http_response_code($status);

  header('Cache-Control: no-store');
  header('Content-Type: text/html; charset=utf-8');

  /* Bound to the session so a form from elsewhere cannot post here */
  if(empty($_SESSION['csrf']))
  {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
  }

  $csrf = Helpers::escape($_SESSION['csrf']);
  $userField = AUTH_FIELD_USER;
  $passField = AUTH_FIELD_PASS;
  $csrfField = AUTH_FIELD_CSRF;
  $message = $error === ''
    ? ''
    : ('<p class="error">' . Helpers::escape($error) . '</p>');

  echo <<<HTML
<!DOCTYPE HTML>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Sign in</title>
    <style>
      :root { color-scheme: dark; }
      body {
        margin: 0; min-height: 100vh; display: flex;
        align-items: center; justify-content: center;
        background: #1a1c20; color: #d8dade;
        font: 15px/1.5 -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      }
      form {
        width: 100%; max-width: 320px; padding: 28px;
        background: #23262b; border: 1px solid #32363d; border-radius: 4px;
        display: flex; flex-direction: column; gap: 14px;
      }
      h1 { margin: 0 0 4px; font-size: 17px; font-weight: 600; }
      label { display: flex; flex-direction: column; gap: 5px; font-size: 12px; color: #9aa0a8; }
      input[type=text], input[type=password] {
        padding: 9px 10px; font-size: 14px; color: #e8eaed;
        background: #1a1c20; border: 1px solid #3a3f47; border-radius: 3px;
      }
      input:focus-visible { outline: 2px solid #5b8dd6; outline-offset: 1px; }
      button {
        padding: 9px 10px; font-size: 14px; font-weight: 500; cursor: pointer;
        color: #fff; background: #3f6ba8; border: 0; border-radius: 3px;
      }
      button:hover { background: #4a7aba; }
      .error {
        margin: 0; padding: 8px 10px; font-size: 13px;
        color: #f0b3ad; background: #3a2320; border: 1px solid #5c332e; border-radius: 3px;
      }
    </style>
  </head>
  <body>
    <form method="post" autocomplete="on">
      <h1>Sign in</h1>
      {$message}
      <label>Username
        <input type="text" name="{$userField}" autocomplete="username" autofocus required>
      </label>
      <label>Password
        <input type="password" name="{$passField}" autocomplete="current-password" required>
      </label>
      <input type="hidden" name="{$csrfField}" value="{$csrf}">
      <button type="submit">Sign in</button>
    </form>
  </body>
</html>
HTML;

  exit;
}

/**
 * Confirms every configured credential is a password hash
 *
 * Storing a plaintext password would work silently and leave the secret
 * readable on disk, so it is refused rather than accepted.
 *
 * @param Array  $users  Username to hash map
 *
 * @return Boolean
 */
function authCredentialsAreHashed($users)
{
  foreach($users as $credential)
  {
    if(!is_string($credential))
    {
      return false;
    }

    $info = password_get_info($credential);

    if(empty($info['algo']))
    {
      return false;
    }
  }

  return true;
}

/**
 * Requires a signed-in session, or presents the login page
 *
 * Replaces the previous HTTP digest scheme, which fixed the hash to MD5,
 * needed a password equivalent on disk, offered no way to sign out and
 * re-authenticated on every request.
 *
 * @param Array   $users    Username to password hash map
 * @param String  $realm    Unused, kept so the call sites read the same
 * @param Array   $options  Authentication options
 *
 * @return Void
 */
function authenticate($users, $realm, $options = [])
{
  $trustProxy = !empty($options['behind_proxy']);

  /* Configure the cookie before the session starts, or the flags do not apply */
  $params = [
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => authIsSecureRequest($trustProxy),
    'httponly' => true,
    'samesite' => 'Lax'
  ];

  if(PHP_VERSION_ID >= 70300)
  {
    session_set_cookie_params($params);
  } else {
    session_set_cookie_params(
      $params['lifetime'], $params['path'] . '; samesite=' . $params['samesite'],
      $params['domain'], $params['secure'], $params['httponly']
    );
  }

  session_name(AUTH_SESSION_NAME);

  if(session_status() !== PHP_SESSION_ACTIVE)
  {
    session_start();
  }

  /* Signing out is a state change, so it carries the same token as the form */
  if(isset($_GET[AUTH_PARAM_LOGOUT]))
  {
    if(isset($_SESSION['csrf'])
      && hash_equals($_SESSION['csrf'], (string) $_GET[AUTH_PARAM_LOGOUT]))
    {
      $_SESSION = [];

      /**
       * Expire the cookie as well. `session_destroy()` only drops the data on
       * the server, so the browser would keep presenting the old identifier
       * and it would be adopted as the next anonymous session
       */
      if(ini_get('session.use_cookies'))
      {
        $cookie = session_get_cookie_params();
        $expired = time() - 42000;

        if(PHP_VERSION_ID >= 70300)
        {
          setcookie(session_name(), '', [
            'expires' => $expired,
            'path' => $cookie['path'],
            'domain' => $cookie['domain'],
            'secure' => $cookie['secure'],
            'httponly' => $cookie['httponly'],
            'samesite' => isset($cookie['samesite']) && $cookie['samesite'] !== ''
              ? $cookie['samesite']
              : 'Lax'
          ]);
        } else {
          setcookie(
            session_name(), '', $expired, $cookie['path'],
            $cookie['domain'], $cookie['secure'], $cookie['httponly']
          );
        }
      }

      session_destroy();
    }

    header('Location: ' . authLocalTarget(false));

    exit;
  }

  if(!is_array($users) || $users === [])
  {
    error_log('IVFi: authentication is enabled but no users are configured');

    authRenderLogin('Authentication is misconfigured.', 500);
  }

  if(!authCredentialsAreHashed($users))
  {
    error_log(
      'IVFi: authentication credentials must be password_hash() values. ' .
      'Generate one with: php -r \'echo password_hash("your password", PASSWORD_DEFAULT), "\n";\''
    );

    authRenderLogin('Authentication is misconfigured.', 500);
  }

  /* Already signed in? */
  if(isset($_SESSION['user'], $_SESSION['seen'])
    && isset($users[$_SESSION['user']])
    && (time() - (int) $_SESSION['seen']) < AUTH_IDLE_TIMEOUT)
  {
    $_SESSION['seen'] = time();

    return;
  }

  /* An expired or unrecognised session should not linger */
  if(isset($_SESSION['user']))
  {
    unset($_SESSION['user'], $_SESSION['seen']);
  }

  if(($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST')
  {
    authRenderLogin();
  }

  $username = isset($_POST[AUTH_FIELD_USER]) ? (string) $_POST[AUTH_FIELD_USER] : '';
  $password = isset($_POST[AUTH_FIELD_PASS]) ? (string) $_POST[AUTH_FIELD_PASS] : '';
  $token = isset($_POST[AUTH_FIELD_CSRF]) ? (string) $_POST[AUTH_FIELD_CSRF] : '';

  if(empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $token))
  {
    authRenderLogin('Your session expired. Please try again.');
  }

  $locked = authThrottle($options, 'check');

  if($locked > 0)
  {
    authRenderLogin(sprintf(
      'Too many attempts. Try again in %d minutes.', (int) ceil($locked / 60)
    ), 429);
  }

  /**
   * Verify against a dummy hash when the user is unknown, so that a missing
   * account takes the same time as a wrong password and cannot be told apart
   */
  $known = isset($users[$username]);
  $hash = $known
    ? $users[$username]
    : '$2y$12$usesomesillystringfortestingpvHrJk3G6.Ll5Qm6Tx0RGa4jXe2Wm';

  if(!password_verify($password, $hash) || !$known)
  {
    authThrottle($options, 'fail');

    /* One message for both cases, so it reveals nothing about which failed */
    authRenderLogin('Incorrect username or password.');
  }

  authThrottle($options, 'clear');

  /* A new identifier on login, so a fixed one cannot be reused */
  session_regenerate_id(true);

  $_SESSION['user'] = $username;
  $_SESSION['seen'] = time();
  $_SESSION['csrf'] = bin2hex(random_bytes(32));

  /* Redirect so a refresh does not repost the form */
  header('Location: ' . authLocalTarget());

  exit;
}

/**
 * Extracts themes from a given path
 *
 * @param String   $basePath     The given base path of the script
 * @param String   $themesPath   A themes path relative to the base path
 * 
 * @return Array
 */ 
function getThemes($basePath, $themesPath)
{
  /* Returnable array */
  $themesPool = [];

  /* Create the absolute path of the directory to scan */
  $absDir = rtrim(Helpers::joinPaths($basePath, $themesPath), DIRECTORY_SEPARATOR);

  if(is_dir($absDir))
  {
    /** Iterates over the given path */
    foreach(scandir($absDir, SCANDIR_SORT_NONE) as $item)
    {
      /** Current iterated item (folder || file) */
      $itemPath = Helpers::joinPaths($absDir, $item);

      if($item[0] !== '.')
      {
        if(is_dir($itemPath))
        {
          /* The current item is assumed to be a theme directory */
          foreach(preg_grep('/^(' . $item . '|index)\.css$/', scandir(
            $itemPath, SCANDIR_SORT_NONE)
          ) as $theme)
          {
            if($theme[0] !== '.')
            {
              $themesPool[strtolower($item)] = [
                'path' => Helpers::joinPaths($themesPath, $item, $theme)
              ];
              
              break;
            }
          }
        } else if(preg_match('~\.css$~', $item))
        {
          /* The current item is a single .CSS file */
          $themesPool[strtolower(basename($item, '.css'))] = [
            'path' => Helpers::joinPaths($themesPath, $item)
          ];
        }
      }
    }

    return $themesPool;
  } else {
    return false;
  }
}

/**
 * Attempts to search for a configuration file.
 * 
 * If it exists, the default values will be overwritten.
 * Any unset values in the file will take the default values.
 */
if(file_exists(CONFIG_FILE))
{
  $config = include(CONFIG_FILE);
  /* Also check for hidden (.) file */
} else if(file_exists('.' . CONFIG_FILE))
{
  $config = include('.' . CONFIG_FILE);
}

/* Default configuration values. Used if values from the above config are unset */
$defaults = array('authentication' => false,'single_page' => false,'format' => array('title' => 'Index of %s','date' => array('m/d/y H:i', 'd/m/y'),'sizes' => array(' B', ' KiB', ' MiB', ' GiB', ' TiB')),'icon' => array('path' => '/favicon.png','mime' => 'image/png'),'sorting' => array('enabled' => false,'order' => SORT_ASC,'types' => 0,'sort_by' => 'name','use_mbstring' => false),'gallery' => array('enabled' => true,'reverse_options' => false,'scroll_interval' => 50,'list_alignment' => 0,'fit_content' => true,'image_sharpen' => false),'preview' => array('enabled' => true,'hover_delay' => 75,'cursor_indicator' => true),'extensions' => array('image' => array('jpg', 'jpeg', 'png', 'gif', 'ico', 'svg', 'bmp', 'webp'),'video' => array('webm', 'mp4', 'ogv', 'ogg', 'mov')),'inject' => false,'style' => array('themes' => array('path' => '/<%= indexerPath %>/themes/','default' => false),'css' => array('additional' => false),'compact' => false),'filter' => array('file' => false,'directory' => false),'exclude' => false,'directory_sizes' => array('enabled' => false, 'recursive' => false),'processor' => false,'encode_all' => false,'allow_direct_access' => false,'path_checking' => 'strict','trust_prepend_header' => false,'performance' => false,'footer' => array('enabled' => true, 'show_server_name' => true),'credits' => true,'debug' => false);

/**
 * Call authentication function
 */
if(isset($config['authentication'])
  && $config['authentication']
  && is_array($config['authentication']))
{
  /* If `users` key is an array, make way for it and check for restrictions */
  if(isset($config['authentication']['users']) &&
    is_array($config['authentication']['users']))
  {
    $isRestricted = true;

    /* A `restrict` key is set, check if it matches current path */
    if(isset($config['authentication']['restrict']) &&
      is_string($config['authentication']['restrict']))
    {
      /* Check if `restrict` filter matches the current requested URI */
      $isRestricted = preg_match($config['authentication']['restrict'], CURRENT_URI);
    }

    /* Restrict content if `restrict` filter matches successfully or it is unset */
    if($isRestricted)
    {
      authenticate(
        $config['authentication']['users'],
        'Restricted content.',
        $config['authentication']
      );
    }
  } else {
    /* Don't use any potential `users` array to authenticate, use main array instead */
    authenticate($config['authentication'], 'Restricted content.', []);
  }
}

/**
 * Set default configuration values if the config is missing any keys
 * 
 * This does not traverse too deep at all
 */
foreach($defaults as $key => $value)
{
  if(!isset($config[$key]))
  {
    $config[$key] = $defaults[$key];
  } else if(is_array($config[$key]) &&
    is_array($defaults[$key]))
  {
    foreach($defaults[$key] as $k => $v)
    {
      if(!isset($config[$key][$k]))
      {
        $config[$key][$k] = $defaults[$key][$k];
      }
    }
  }
}

/* Used to bust the cache (query-strings for js and css files) */
$bust = md5($config['debug'] ? time() : $version);

/* Default stylesheet output */
$baseStylesheet = sprintf(
  '<link rel="stylesheet" type="text/css" href="<%= indexerPath %>css/style.css?bust=%s">',
  $bust
);

/**
 * Set debugging
 */
if($config['debug'] === true)
{
  ini_set('display_errors', 1);
  ini_set('display_startup_errors', 1);
  error_reporting(E_ALL);
}

/**
 * Set footer data
 */
$footer = [
  'enabled' => is_array(
    $config['footer'])
      ? ($config['footer']['enabled'] ? true : false)
      : ($config['footer'] ? true : false),
  'show_server_name' => is_array(
    $config['footer'])
      ? $config['footer']['show_server_name']
      : true
];

/**
 * Set start time for page render calculations
 */
if($footer['enabled'])
{
  $render = microtime(true);
}

if($config['style']['themes']['path'])
{
  $config['style']['themes']['path'] = Helpers::stringWrap(
    $config['style']['themes']['path'], '/'
  );
}

if(!is_array($config['format']['date']))
{
  $config['format']['date'] = [is_string($config['format']['date'])
    ? $config['format']['date']
    : 'd/m/y H:i', 'd/m/y'
  ];
}

/**
 * Indexer Class
 */ 
class Indexer extends Helpers
{
  public $path;

  public $timestamp;

  private $exclude;

  private $client;

  private $format;

  private $filter;

  private $directorySizes;

  private $relative;

  private $pathPrepend;

  private $requested;

  private $types;

  private $allowDirectAccess;

  private $encodeAll;

  private $processor;

  private $debug;

  function __construct($path, $options = [])
  {
    /* Get requested path */
    $requested = rawurldecode(strpos($path, '?') !== false ? explode('?', $path)[0] : $path);

    /* Set relative path */
    if(isset($options['path']['relative'])
      && $options['path']['relative'] !== NULL)
    {
      $this->relative = $options['path']['relative'];
    } else {
      $this->relative = dirname(__FILE__);
    }

    /* Set encode all options */
    $this->encodeAll = $options['encode_all'] ? true : false;

    if(isset($options['path']['prepend'])
      && $options['path']['prepend'] !== NULL
      && strlen($options['path']['prepend']) >= 1)
    {
      $this->pathPrepend = ltrim(
        rtrim($options['path']['prepend'], '/'), '/'
      );
    } else {
      $this->pathPrepend = NULL;
    }

    /* Declare array for optional processing of data */
    $this->processor = [
      'item' => NULL
    ];

    /* Check for passed processing functions */
    if(isset($options['processor']) && is_array($options['processor']))
    {
      if(isset($options['processor']['item']))
      {
        $this->processor['item'] = $options['processor']['item'];
      }
    }

    /* Set remaining options/variables */
    $this->client = isset($options['client']) ? $options['client'] : NULL;
    $this->path = rtrim($this->joinPaths($this->relative, $requested), '/');
    $this->timestamp = time();
    $this->debug = $options['debug'];
    $this->directorySizes = $options['directory_sizes'];
    $this->allowDirectAccess = isset($options['allow_direct_access'])
      ? $options['allow_direct_access']
      : true;

    /* Is requested path a directory? */
    if(is_dir($this->path))
    {
      /* Check if the directory is above the base directory (or same level) */
      if(self::isAboveCurrent($this->path, $this->relative))
      {
        $this->requested = $requested;
      } else {
        /* Directory is below the base directory */
        if($options['path_checking'] === 'strict' || $options['path_checking'] !== 'weak')
        {
          throw new Exception(
            "requested path (is_dir) is below the public working directory. (mode: {$options['path_checking']})", 1
          );
        } else if($options['path_checking'] === 'weak')
        {
          /**
           * If path checking is 'weak' do another test using a 'realpath' alternative
           * instead (string-based approach which doesn't solve links)
           */
          if(self::isAboveCurrent($this->path, $this->relative, false)
            || is_link($this->path))
          {
            $this->requested = $requested;
          } else {
            /* Even the 'weak' check failed, throw an exception */
            throw new Exception(
              "requested path (is_dir) is below the public working directory. (mode: {$options['path_checking']})", 2
            );
          }
        }
      }
    } else {
      /* Is requested path a file (this can only be the Indexer as we don't have control over any other files)? */
      if(is_file($this->path))
      {
        /* If direct access is disabled, deny access */
        if($this->allowDirectAccess === false)
        {
          http_response_code(403);
          die('Forbidden');
        } else {
          /* If direct access is allowed, show current directory of script (if it is above base directory) */
          $this->path = dirname($this->path);

          if(self::isAboveCurrent($this->path, $this->relative))
          {
            $this->requested = dirname($requested);
          } else {
            throw new Exception(
              'requested path (is_file) is below the public working directory.', 3
            );
          }
        }
      } else {
        /* If requested path is neither a file nor a directory */
        throw new Exception('invalid path. path does not exist.', 4);
      }
    }

    /* Set extension variables */
    if(isset($options['extensions']))
    {
      $this->types = [];

      foreach($options['extensions'] as $type => $value)
      {
        foreach($options['extensions'][$type] as $extension)
        {
          $this->types[strtolower($extension)] = $type;
        }
      }
    } else {
        $this->types = [
          'jpg' => 'image',
          'jpeg' => 'image',
          'gif' => 'image',
          'png' => 'image',
          'ico' => 'image',
          'svg' => 'image',
          'bmp' => 'image',
          'webp' => 'image',
          'webm' => 'video',
          'mp4' => 'video',
          'ogg' => 'video',
          'ogv' => 'video'
        ];
    }

    /* Set filter variables */
    if(isset($options['filter']) && is_array($options['filter']))
    {
      $this->filter = $options['filter'];
    } else {
      $this->filter = [
        'file' => false,
        'directory' => false
      ];
    }

    /* Set exclusion variables */
    if(isset($options['exclude']) && is_array($options['exclude']))
    {
      $this->exclude = $options['exclude'];
    } else {
      $this->exclude = false;
    }

    /* Set size format variables */
    if(isset($options['format']['sizes']) && $options['format']['sizes'] !== NULL)
    {
      $this->format['sizes'] = $options['format']['sizes'];
    } else {
      $this->format['sizes'] = [' B', ' KiB', ' MiB', ' GiB', ' TiB', ' PB', ' EB', ' ZB', ' YB'];
    }

    $this->format['date'] = $options['format']['date'];
  }

  /**
   * Handles pathing by taking any potential prepending into mind
   *
   * @param String    $path    A path
   * @param Boolean   $isDir   Whether the path should be treated as a directory
   * 
   * @return String
   */ 
  private function handlePathing($path, $isDir = true)
  {
    $path = ltrim(rtrim($path, '/'), '/');

    if($this->pathPrepend)
    {
      if(!empty($path))
      {
        $path = sprintf(
          '/%s/%s%s',
          $this->pathPrepend,
          $path,
          $isDir ? '/' : ''
        );
      } else {
        $path = '/' . $this->pathPrepend . '/';
      }
    } else {
      $path = ('/' . $path . (!empty($path) && $isDir ? '/' : ''));
    }

    return $path;
  }

  /**
   * Handles the construction of the rows for the files
   *
   * @param Array    $files    An array of files
   * 
   * @return Array
   */
  private function constructRowsFiles($files)
  {
    /** Rows (HTML) */
    $rows = [];

    /** Most recently modified directory */
    $mostRecentTimestamp = 0;

    /** Total size of all directories */
    $totalSize = 0;

    /* Iterate over the files, get and store data */
    foreach($files as $file)
    {
      /** Deconstruction of array */
      list($fileName, $fileSize, $fileUrl, $fileType, $fileModified) = [
        $file[1],
        $file['size'],
        $file['url'],
        $file['type'],
        $file['modified']
      ];

      /** Append to total size */
      $totalSize = ($totalSize + $fileSize[0]);

      /** Set most recent timestamp if applicable */
      if($mostRecentTimestamp === 0
        || $fileModified[0] > $mostRecentTimestamp)
      {
        $mostRecentTimestamp = $fileModified[0];
      }

      /** File name anchor attributes */
      $anchorAttributes = [
        'href' => $this->handlePathing($fileUrl, false)
      ];

      /** If file is an image or video, add preview class */
      if($fileType[0] === 'image' || $fileType[0] === 'video')
      {
        $anchorAttributes['class'] = 'preview';
      }

      /** Create file name column */
      $tdFileName = parent::createElement('td', [
        'data-raw' => $fileName
      ], parent::createElement(
        'a', $anchorAttributes, $fileName
      ), true);

      /** Create modified column */
      $tdModified = parent::createElement('td', [
        'data-raw' => $fileModified[0]
      ], implode('', [
        parent::createElement(
          'span', [], $fileModified[1], true
        )
      ]), true);

      /** Create size column */
      $tdSize = parent::createElement('td', [
        'data-raw' => $fileSize[0] === -1 ? 0 : $fileSize[0]
      ], $fileSize[1]);

      /** Create save anchor */
      $anchorSave = parent::createElement('a', [
        'href' => $fileUrl,
        'filename' => $fileName,
        'download' => ''
      ], implode('', [
        parent::createElement(
          'span', ['data-view' => 'desktop'], '[Download]'
        ),
        parent::createElement(
          'span', ['data-view' => 'mobile'], '[Save]'
        )
      ]), true);

      /** Create save column */
      $tdSave = parent::createElement('td', [
        'data-raw' => $fileType[0],
        'class' => 'download'
      ], $anchorSave, true);

      /** Create container and add to rows */
      $rows[] = parent::createElement('tr', [
        'class' => 'file'
      ], implode('', [
        $tdFileName,
        $tdModified,
        $tdSize,
        $tdSave
      ]), true);
    }

    return [
      'rows' => $rows,
      'totalSize' => $totalSize,
      'mostRecentTimestamp' => $mostRecentTimestamp
    ];
  }

  /**
   * Handles the construction of the rows for the directories
   *
   * @param Array    $fildirectorieses    An array of directories
   * 
   * @return Array
   */
  private function constructRowsDirectory($directories)
  {
    /** Rows (HTML) */
    $rows = [];

    /** Most recently modified directory */
    $mostRecentTimestamp = 0;

    /** Total size of all directories */
    $totalSize = 0;

    /* Iterate over the directories, get and store data */
    foreach($directories as $dir)
    {
      /** Directory URL */
      $url = $this->handlePathing($dir['url'], true);

      /** Directory size */
      $size = $this->directorySizes['enabled']
        ? self::getReadableFileSize($dir['size'])
        : '-';

      if($this->directorySizes['enabled'])
      {
        $totalSize = ($totalSize + $dir['size']);
      }

      /** Create directory name column */
      $tdDirectoryName = parent::createElement('td', [
        'data-raw' => $dir[1]
      ], parent::createElement(
        'a', [
          'href' => $url
        ], '[' . $dir[1] . ']'
      ), true);

      /** Create modified column */
      $tdModified = parent::createElement('td', [
        'data-raw' => $dir['modified'][0]
      ], implode('', [
        parent::createElement(
          'span', [], $dir['modified'][1], true
        )
      ]), true);

      /** Create size column */
      $tdSize = parent::createElement('td', $this->directorySizes['enabled']
        ? ['data-raw' => $dir['size']]
        : [], $size
      );

      $tdType = parent::createElement(
        'td', [], parent::createElement('span', [], '-'), true
      );

      /** Create container and add to rows */
      $rows[] = parent::createElement('tr', [
        'class' => 'directory'
      ], implode('', [
        $tdDirectoryName,
        $tdModified,
        $tdSize,
        $tdType
      ]), true);

      if(($mostRecentTimestamp === 0)
        || $dir['modified'][0] > $mostRecentTimestamp)
      {
        $mostRecentTimestamp = $dir['modified'][0];
      }
    }

    return [
      'rows' => $rows,
      'totalSize' => $totalSize,
      'mostRecentTimestamp' => $mostRecentTimestamp
    ];
  }

  /**
   * Gets file/directory information and constructs the HTML of the table
   *
   * @param String    $sorting    Server-side sorting to use
   * @param Integer   $sortItems  What type of items to sort
   * @param String    $sortType   What to sort by
   * @param Boolean   $sortType   Whether to use mb_* functions for sorting
   * 
   * @return String
   */ 
  public function buildTable($sorting = false, $sortItems = 0, $sortType = 'modified', $useMb = false)
  {
    /* Get client timezone offset */
    $cookies = [
      'timezoneOffset' => intval(is_array($this->client)
        ? (isset($this->client['timezoneOffset'])
          ? $this->client['timezoneOffset']
          : 0)
        : 0)
    ];

    $timezone = [
      'offset' => $cookies['timezoneOffset'] > 0
        ? -$cookies['timezoneOffset'] * 60
        : abs($cookies['timezoneOffset']) * 60
    ];

    /* Gets the current directory */
    $directory = self::getCurrentDirectory();

    /* Gets the files from the current path and filter them */
    $files = $this->handleFiles(self::getFiles(), ($directory === '/'));

    /** Parent variables */
    $parentDirectory = dirname($directory);
    $parentHref = $this->handlePathing($parentDirectory, true);

    if($this->pathPrepend)
    {
      $prependedCurrent = ltrim(
        rtrim($this->joinPaths($this->pathPrepend, $directory), '/'), '/'
      );

      $prependedRoot = ltrim(
        rtrim($this->pathPrepend, '/'), '/'
      );

      if($prependedCurrent === $prependedRoot)
      {
        $steppedPath = dirname('/' . $prependedRoot . '/');
        $parentHref = str_replace(
          '\\\\', '\\', $steppedPath . (substr($steppedPath, -1) === '/' ? '' : '/')
        );
      }
    }

    /** Construct HTML */
    $HTML = parent::createElement('tr', [
      'class' => 'parent'
    ], implode('', array_merge([
      parent::createElement('td', [], parent::createElement('a', [
          'href' => $parentHref
        ], '[Parent Directory]'), true)
      ],
      array_fill(0, 3, parent::createElement('td', [], parent::createElement(
        'span', [], '-'
      ), true)))
    ), true);

    /** Request data */
    $data = [
      'files' => $files['files'],
      'directories' => $files['directories'],
      'readme' => $files['readme'],
      'dotFile' => $files['dotFile'],
      'recent' => [
        'file' => 0,
        'directory' => 0
      ],
      'size' => [
        'total' => 0,
        'readable' => 'N/A'
      ]
    ];

    if($useMb === true
      && !function_exists('mb_strtolower'))
    {
      http_response_code(500);

      die(
        'Error (mb_strtolower is not defined): In order to use mbstring, you\'ll need to ' .
        '<a href="https://www.php.net/manual/en/mbstring.installation.php">install</a> ' .
        'it first.'
      );
    }

    /**
     * Iterate over the gathered directories and set their data
     */
    foreach($data['directories'] as $index => $dir)
    {
      /** Deconstruct array */
      list($dirPath, $dirName) = $dir;

      $item = &$data['directories'][$index];

      /* We only need to set 'name' key if we're sorting by name */
      if($sortType === 'name')
      {
        $item['name'] = $useMb === true
          ? mb_strtolower($dirName, 'UTF-8')
          : strtolower($dirName);
      }

      /* Set directory data values */
      $item['modified'] = self::getModified($dirPath, $timezone['offset']);
      $item['type'] = 'directory';
      $item['url'] = rtrim($this->joinPaths($this->requested, $dirName), '/');
      $item['size'] = $this->directorySizes['enabled']
        ? ($this->directorySizes['recursive']
          ? self::getDirectorySizeRecursively($dirPath)
          : self::getDirectorySize($dirPath))
        : 0;
    }

    /**
     * Iterate over the gathered files and set their data
     */
    foreach($data['files'] as $index => $file)
    {
      /** Deconstruct array */
      list($filePath, $fileName, $fileType) = $file;

      $item = &$data['files'][$index];

      /* We only need to set 'name' key if we're sorting by name */
      if($sortType === 'name')
      {
        $item['name'] = $useMb === true
          ? mb_strtolower($fileName, 'UTF-8')
          : strtolower($fileName);
      }

      /* Set file data values */
      $item['type'] = $fileType;
      $item['size'] = self::getSize($filePath);
      $item['modified'] = self::getModified($filePath, $timezone['offset']);
      $item['url'] = rtrim($this->joinPaths($this->requested, $fileName), '/');

      /** Encode URL if `encode_all` is enabled */
      if($this->encodeAll)
      {
        $item['url'] = str_replace('?', '%3F', str_replace('#', '%23', $item['url']));
      }
    }

    /* Pass data to processor if it is set */
    if($this->processor['item'])
    {
      $data = $this->processor['item']($data, $this);
    }

    /* Sort items server-side */
    if($sorting)
    {
      if($sortItems === 0 || $sortItems === 1)
      {
        array_multisort(
          array_column($data['files'], $sortType),
          $sorting,
          $data['files']
        );
      }

      if($sortItems === 0 || $sortItems === 2)
      {
        array_multisort(
          array_column($data['directories'], $sortType),
          $sorting,
          $data['directories']
        );
      }
    }

    /** Get directory rows data */
    $directoryRows = $this->constructRowsDirectory($data['directories']);

    /** Get file rows data */
    $fileRows = $this->constructRowsFiles($data['files']);

    /** Implode directory and files rows */
    $HTML .= (implode(PHP_EOL, $directoryRows['rows']) . implode(PHP_EOL, $fileRows['rows']));
  
    /** Set request data */
    $data['size']['total'] = ($directoryRows['totalSize'] + $fileRows['totalSize']);
    $data['recent']['directory'] = $directoryRows['mostRecentTimestamp'];
    $data['recent']['file'] = $fileRows['mostRecentTimestamp'];

    /** Get readable size */
    $data['size']['readable'] = self::getReadableFileSize($data['size']['total']);

    return [
      'contents' => $HTML,
      'data' => $data
    ];
  }

  /**
   * Gets the current files from set path
   */
  private function getFiles()
  {
    return scandir($this->path, SCANDIR_SORT_NONE);
  }

  /**
   * Gets the currently requested directory
   */
  public function getCurrentDirectory()
  {
    $requested = trim($this->requested);

    if($requested === '/'
      || $requested === '\\'
      || empty($requested))
    {
      return '/';
    } else {
      return preg_replace(
        '#/+#','/',
        $requested[strlen($requested) - 1] === '/'
          ? rtrim($requested, '/') . '/'
          : rtrim($requested, '/')
      );
    }
  }

  /**
   * Identifies file type by matching it against the extension arrays
   *
   * @param String    $filename     Filename
   * 
   * @return Array
   */ 
  private function getFileType($filename)
  {
    $extension = strtolower(ltrim(pathinfo($filename, PATHINFO_EXTENSION), '.'));

    return [isset($this->types[$extension])
      ? $this->types[$extension]
      : 'other', $extension
    ];
  }

  /**
   * Converts the current path into clickable anchors
   *
   * @param String    $path     URI public path
   * 
   * @return Array
   */ 
  public function makePathClickable($path)
  {
    $output = parent::createElement('a', [
      'href' => '/'
    ], '/');

	  $path = $this->handlePathing($path, true);

    $items = explode('/', ltrim($path, '/'));

    foreach($items as $i => $p)
    {
      $i++; $text = (($i !== 1 ? '/' : '') . $p);

      if($text === '/')
      {
        continue;
      }

      $output .= Helpers::createElement('a', [
        'href' => sprintf('/%s', implode(
          '/', array_slice($items, 0, $i)
        ))
      ], ($i === (count($items) - 1))
        ? rtrim($text, '/') . '/'
        : $text
      );
    }

    return $output;
  }

  /**
   * Formats a unix timestamp
   *
   * @param String    $format     String formatting
   * @param Integer   $stamp      Timestamp
   * @param Integer   $modifier   An integer that gets added to the timestamp
   * 
   * @return String
   */ 
  private function formatDate($format, $stamp, $modifier = 0)
  {
    return gmdate($format, $stamp + $modifier);
  }

  /**
   * Gets the last modified date of a file
   *
   * @param String    $path       File path
   * @param Integer   $modifier   An integer that gets added to the timestamp
   * 
   * @return Array
   */ 
  private function getModified($path, $modifier = 0)
  {
    $stamp = filemtime($path);

    if(count($this->format['date']) === 2)
    {
      $formatted = "";

      for($i = 0; $i < 2; ++$i)
      {
        $format = self::formatDate(
          $this->format['date'][$i], $stamp, $modifier
        );

        $formatted .= parent::createElement('span', [
          'data-view' => $i === 0 ? 'desktop' : 'mobile'
        ], $format);
      }
    } else {
      /**
       * Encode here so that the second element is uniformly safe markup in
       * both branches, since callers wrap it without escaping
       */
      $formatted = parent::escape(self::formatDate(
        $this->format['date'][0], $stamp, $modifier
      ));
    }

    return [$stamp, $formatted];
  }

  /**
   * Reads and returns potential filters from a dotfile
   *
   * @param Array    $file       Dotfile array
   * 
   * @return Array
   */ 
  private function getDotFileFilters($file)
  {
    /** Regular expressions */
    $expDirs = []; $expFiles = [];

    /** Handles ignored files */
    if(isset($file['ignore']) && is_array($file['ignore']))
    {
      $ignored = [];

      foreach($file['ignore'] as $expression)
      {
        if(!$expression || empty($expression))
        {
          continue;
        }

        /** Escape string and convert it to a wildcard expression */
        $regex = str_replace(
          '\*', '.*', preg_quote($expression, '/')
        ) . '$';

        array_push($ignored, $regex);
      }

      if(count($ignored) > 0)
      {
        /** Create group expression */
        array_push($expDirs, '/^(?!' . (
          implode('|', $ignored)
        ) . ').*$/');

        /** Create group expression */
        array_push($expFiles, '/^(?!' . (
          implode('|', $ignored)
        ) . ').*$/');
      }
    }

    /** Handles exluded extensions */
    if(isset($file['exclude']) && is_array($file['exclude']))
    {
      foreach($file['exclude'] as $extension)
      {
        if(!$extension || empty($extension))
        {
          continue;
        }

        /**
         * It may be better to not use regular expressions when excluding
         * certain extensions, however, with the current setup, streamlining
         * the process is easier since we are already doing the same thing
         * with the `ignore` feature.
         * 
         * In the future, this can be changed to use a simple `endsWith` check or
         * incorporated into the actual extension matching used when doing exclusion
         * through the config.
         */
        array_push(
          $expFiles, '/^(?!' . ('.*\.' . $extension) . '$).*$/'
        );
      }
    }

    return [
      'ignore' => [
        'file' => $expFiles,
        'directory' => $expDirs
      ]
    ];
  }

  /**
   * Filters a set of gathered files
   *
   * @param Array     $files    Array of files
   * @param Boolean   $isBase   Whether or not the current path is the base path
   * 
   * @return Array
   */ 
  private function handleFiles($files, $isBase)
  {
    /* Gets the filename of this script */
    $scriptName = basename(__FILE__);

    $data = array(
      'files' => array(),
      'directories' => array(),
      'readme' => NULL,
      'dotFile' => NULL
    );

    /**
     * [Check for dotfile presence]
     * 
     * It may contain filters for the current directory, so it's
     * convenient to check for its existence before filtering.
     * 
     * array_flip+isset is used because it's the most consistent when
     * it comes to performance over a wide range of directory lenghts.
     * 
     * @see https://gist.github.com/ksimka/21a6ff74b41451c430e8
     */
    if(isset(array_flip($files)[DOTFILE_NAME]))
    {
        /** Read file as JSON */
        $data['dotFile'] = $this->readJson(
          $this->joinPaths($this->path, DOTFILE_NAME)
        );
    }

    /** Set used filters */
    $usedFilters = [
      'directory' => $this->filter['directory'] ?? [],
      'file' => $this->filter['file'] ?? []
    ];

    /** Get extra filters */
    $dotFilters = $data['dotFile']
      ? $this->getDotFileFilters($data['dotFile'])
      : [];

    if(isset($dotFilters['ignore']))
    {
      /** Add any extra filters from dotfile */
      foreach(['directory', 'file'] as $filterType)
      {
        if(!$usedFilters[$filterType])
        {
          $usedFilters[$filterType] = [];
        } else if(!is_array($usedFilters[$filterType]))
        {
          $usedFilters[$filterType] = is_string($usedFilters[$filterType])
          ? [$usedFilters[$filterType]]
          : [];
        }
      }

      /** Push file filters */
      array_push(
        $usedFilters['file'],
        ...$dotFilters['ignore']['file']
      );

      /** Push directory filters */
      array_push(
        $usedFilters['directory'],
        ...$dotFilters['ignore']['directory']
      );
    }

    foreach($files as $file)
    {
      /** Skip hidden files */
      if($file[0] === '.')
      {
        continue;
      }

      $filePath = ($this->path . '/' . $file);
      $skipItem = false;

      if(is_dir($filePath))
      {
        if($isBase && $file === 'indexer')
        {
          /** Ignore `indexer` directory */
          continue;
        } else if($usedFilters['directory'] !== false)
        {
          if(is_array($usedFilters['directory']))
          {
            foreach($usedFilters['directory'] as $filter)
            {
              if(!preg_match($filter, $file . '/'))
              {
                $skipItem = true;
                break;
              }
            }
          } else if(!preg_match($usedFilters['directory'], $file . '/'))
          {
            /** Ignore directories matching any potential filter */
            continue;
          }
        }

        if(!$skipItem)
        {
          array_push(
            $data['directories'], array($filePath, $file)
          );
        }
      } else if(file_exists($filePath))
      {
        if($file === 'README.md')
        {
          /** Set README data */
          $data['readme'] = $filePath;
        }
        
        if($isBase && $file === $scriptName)
        {
          continue;
        } else if($usedFilters['file'] !== false)
        {
          if(is_array($usedFilters['file']))
          {
            foreach($usedFilters['file'] as $filter)
            {
              if(!preg_match($filter, $file))
              {
                $skipItem = true;
                break;
              }
            }
          } else if(!$skipItem)
          {
            $skipItem = !preg_match($usedFilters['file'], $file);
          }
        }

        $fileType = $this->getFileType($file);

        if(($this->exclude && is_array($this->exclude))
          && in_array($fileType[1], $this->exclude))
        {
          $skipItem = true;
        }

        if($skipItem)
        {
          continue;
        }

        array_push(
          $data['files'],
          array($filePath, $file, $fileType)
        );
      }
    }

    return $data;
  }

  /**
   * Gets a client cookie key
   *
   * @param String    $path       File path
   * @param Integer   $modifier   An integer that gets added to the timestamp
   * 
   * @return Array
   */ 
  private function getCookie($key, $default = NULL)
  {
    return isset($_COOKIE[$key]) ? $_COOKIE[$key] : $default;
  }

  /**
   * Gets the size of a file
   *
   * @param String   $path   File path
   * 
   * @return Array
   */ 
  private function getSize($path)
  {
    $fs = filesize($path);
    $size = ($fs < 0 ? -1 : $fs);

    return array($size, self::getReadableFileSize($size));
  }

  /**
   * Gets the size of a directory
   *
   * @param String   $path   File path
   * 
   * @return Integer
   */ 
  private function getDirectorySize($path)
  {
    $size = 0;

    try
    {
      foreach(scandir($path, SCANDIR_SORT_NONE) as $file)
      {
        if($file[0] === '.')
        {
          continue;
        } else {
          $filesize = filesize($this->joinPaths($path, $file));

          if($filesize && $filesize > 0)
          {
            $size += $filesize;
          }
        }
      }
    } catch (Exception $e)
    {
      $size += 0;
    }

    return $size;
  }

  /**
   * Gets the full size of a director using recursive scanning
   *
   * @param String   $path   File path
   * 
   * @return Integer
   */ 
  private function getDirectorySizeRecursively($path)
  {
    $size = 0;

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));

    try
    {
      foreach($iterator as $file)
      {
        if($file->isDir())
        {
          continue;
        } else {
          $size += filesize($file->getPathname());
        }
      }
    } catch (Exception $e)
    {
      $size += 0;
    }

    return $size;
  }

  /**
   * Converts bytes to a readable file size
   *
   * @param Integer   $bytes      File size in bytes
   * @param Integer   $decimals   # of decimals in the readable output
   * 
   * @return String
   */ 
  private function getReadableFileSize($bytes, $decimals = 1)
  {
    if($bytes === 0)
    {
      return '0' . $this->format['sizes'][0];
    }

    $base = log($bytes, 1024);
    $floored = floor($base);
    $value = pow(1024, $base - $floored);

    if($value >= 100)
    {
      $decimals = 0;
    }

    return round($value, $decimals) . $this->format['sizes'][$floored];
  }
}

/** Define (and get) cookie array */
$cookies = array(
  'readme' => array(
    'toggled' => isset($client['readme']['toggled'])
      ? $client['readme']['toggled']
      : true
  ),
  'sorting' => array(
    'row' => $validate
      ? (isset($client['sort']['row'])
        ? $client['sort']['row']
        : NULL)
      : NULL,
    'ascending' => $validate
      ? (isset($client['sort']['ascending'])
        ? $client['sort']['ascending']
        : NULL)
      : NULL
  )
);

/* Override the config value if the cookie value is set */
if($validate && isset($client['style']['compact'])
  && $client['style']['compact'])
{
  $config['style']['compact'] = $client['style']['compact'];
}

/* Set sorting settings */
$sorting = array(
  'enabled' => $config['sorting']['enabled'],
  'order' => $config['sorting']['order'],
  'types' => $config['sorting']['types'],
  'sort_by' => strtolower($config['sorting']['sort_by'])
);

if($cookies['sorting']['row'] !== NULL)
{
  switch(intval($cookies['sorting']['row']))
  {
    case 0: $sorting['sort_by'] = 'name'; break;
    case 1: $sorting['sort_by'] = 'modified'; break;
    case 2: $sorting['sort_by'] = 'size'; break;
    case 3: $sorting['sort_by'] = 'type'; break;
  }
}

if($cookies['sorting']['ascending'] !== NULL)
{
  $sorting['order'] = (boolval($cookies['sorting']['ascending']) === true
    ? SORT_ASC
    : SORT_DESC
  );
}

/** Enable client-side sorting if it's set */
if($cookies['sorting']['ascending'] !== NULL
  || $cookies['sorting']['row'] !== NULL)
{
  $sorting['enabled'] = true;
}

/* Get `INDEXER_PREPEND_PATH` if set */
if(isset($_SERVER['INDEXER_PREPEND_PATH']))
{
  $prependPath = $_SERVER['INDEXER_PREPEND_PATH'];
} else if($config['trust_prepend_header']
  && isset($_SERVER['HTTP_X_INDEXER_PREPEND_PATH']))
{
  /**
   * Only read from the request header when the operator has opted in. Any
   * client can send this header, and it rewrites every link on the page, so
   * honouring it unconditionally hands link control to the visitor
   */
  $prependPath = $_SERVER['HTTP_X_INDEXER_PREPEND_PATH'];
} else {
  $prependPath = '';
}

/**
 * Constrain the prepend value to something path shaped regardless of where it
 * came from, so a malformed proxy configuration cannot feed arbitrary text
 * into the generated markup
 */
if($prependPath !== '' && !preg_match('#^[A-Za-z0-9._~!$&\'()*+,;=:@/%-]*$#', $prependPath))
{
  error_log(sprintf(
    'IVFi: discarding prepend path containing unexpected characters: %s',
    $prependPath
  ));

  $prependPath = '';
}

try
{
  /* Call class with options set */
  $indexer = new Indexer(
      CURRENT_URI,
      [
          'path' => [
            'relative' => BASE_PATH,
            'prepend' => $prependPath
          ],
          'format' => [
            'date' => isset($config['format']['date']) 
              ? $config['format']['date']
              : NULL,
            'sizes' => isset($config['format']['sizes'])
              ? $config['format']['sizes']
              : NULL
          ],
          'directory_sizes' => $config['directory_sizes'],
          'client' => $client,
          'filter' => $config['filter'],
          'exclude' => $config['exclude'],
          'extensions' => $config['extensions'],
          'path_checking' => strtolower($config['path_checking']),
          'processor' => $config['processor'],
          'encode_all' => $config['encode_all'],
          'debug' => $config['debug'],
          'allow_direct_access' => $config['allow_direct_access']
      ]
  );
} catch (Exception $e) {
  /** Get error code */
  $eCode = $e->getCode();

  /**
   * These are all failures of the request, not of the server.
   *
   * A missing path in particular is routine: the page links a favicon, and any
   * deployment without one asks for a path that does not exist on every single
   * view. Answering that with a 500 misreports it to clients and to monitoring
   */
  if($eCode === 4)
  {
    http_response_code(404);
  } else if($eCode === 1 || $eCode === 2 || $eCode === 3)
  {
    http_response_code(403);
  } else {
    http_response_code(500);
  }

  /**
   * Record the exception server-side, but not for a merely missing path, which
   * would write a log line for every request for anything that isn't there.
   *
   * It is never sent to the client outside of debug mode: the string form
   * carries the stack trace, which exposes absolute filesystem paths and the
   * internal call structure
   */
  if($eCode !== 4)
  {
    error_log(sprintf('IVFi: %s', $e));
  }

  echo Helpers::createElement('h3', [], 'Error:');

  if($config['debug'])
  {
    echo Helpers::createElement('p', [], $e . '({' . $eCode . '})');

    if($eCode === 1 || $eCode === 2)
    {
      echo Helpers::createElement(
        'p', [], sprintf(
          'This error occurs when the requested directory is below the directory of the PHP file. %s',
          $eCode === 1
            ? (
                '<br/>You can try setting <b>path_checking</b> to <b>weak</b> ' .
                'if you are working with symbolic links etc.'
              )
            : ''
        ), true
      );
    }
  } else {
    echo Helpers::createElement(
      'p', [], 'The request could not be completed. Check the server error log for details.'
    );
  }

  exit(Helpers::createElement(
    'p', [], 'Fatal error - Exiting.')
  );
}

/* Get directory data */
$table = $indexer->buildTable(
  $sorting['enabled'] ? $sorting['order'] : false,
  $sorting['enabled'] ? $sorting['types'] : 0,
  $sorting['enabled'] ? strtolower($sorting['sort_by']) : 'modified',
  $sorting['enabled'] ? $config['sorting']['use_mbstring'] : false
);

/** Get the fetched data from the request */
$data = $table['data'];

/** Calculate total items */
$itemsTotal = (count($data['files']) + count($data['directories']));

/* Check if performance mode depends on item count */
if(is_int($config['performance']))
{
  $itemsTotal = (count($data['files']) + count($data['directories']));

  if($itemsTotal >= $config['performance'])
  {
    $config['performance'] = true;
  } else {
    $config['performance'] = false;
  }
}

/* Set some data like file count etc */
$counts = [
    'files' => count($data['files']),
    'directories' => count($data['directories'])
];

if($config['style']['themes']['path'])
{
  $themesPool = getThemes(BASE_PATH, $config['style']['themes']['path']);

  if($themesPool
    && is_array($themesPool)
    && count($themesPool) > 0)
  {
    $themes = array_merge($themes, $themesPool);
  }
}

if(count($themes) > 0)
{
  /* Check if client has a custom theme already set */
  if(is_array($client)
    && isset($client['style']['theme']))
  {
    $currentTheme = $client['style']['theme'] ? $client['style']['theme'] : NULL;
  /* Check for a default theme */
  } else if(isset($config['style']['themes']['default']))
  {
    $defaultTheme = strtolower($config['style']['themes']['default']);

    if($defaultTheme && isset($themes[$defaultTheme]))
    {
      $currentTheme = $defaultTheme;
    }
  }
}

/* Apply compact mode if that is set */
$compact = (is_array($client) && isset($client['style']['compact']))
  ? $client['style']['compact']
  : $config['style']['compact'];

if(is_array($config['style']['css']['additional']))
{
  foreach($config['style']['css']['additional'] as $key => $value)
  {
    $selector = $key; $values = '';

    foreach($value as $key => $value)
    {
      $values .= sprintf('%s:%s;', $key, rtrim($value, ';'));
    }

    $additionalCss .= sprintf('%s{%s}', $selector, $values);
  }
} else if(is_string($config['style']['css']['additional']))
{
  $additionalCss .= str_replace(
    '"', '\"', $config['style']['css']['additional']
  );
}

/* Alternative stylesheet output for when single-page is enabled */
if($config['single_page'])
{
  /* Check if `navigateType` is set */
  if($_SERVER['REQUEST_METHOD'] === 'POST' && 
    isset($_POST['navigateType']) && $_POST['navigateType'] === 'dynamic')
  {
    /* Set a header to identify the response on the client side */
    header('navigate-type: dynamic');

    $stylePath = $indexer->joinPaths(BASE_PATH, '<%= indexerPath %>', '/css/style.css');

    if(file_exists($stylePath))
    {
      $styleData = file_get_contents($stylePath);

      /* If any additional CSS is set, merge that with this output */
      if(!empty($additionalCss))
      {
        $styleData .= (' ' . $additionalCss);
        $additionalCss = '';
      }

      $baseStylesheet = Helpers::createElement('style', [
        'type' => 'text/css'
      ], $styleData);
    }
  }
}

if($config['inject'])
{
  /* Current path */
  $injectPassableData['path'] = $indexer->getCurrentDirectory();
  /* Get file and directory counts */
  $injectPassableData['counts'] = $counts;
  /* Get directory size */
  $injectPassableData['size'] = $data['size'];
  /* Pass config values */
  $injectPassableData['config'] = $config;
}

/**
 * Gets the inject options
 *
 * @param String   $key   Inject key (`head`, `body` or `footer`)
 * 
 * @return String
 */ 
$getInjectable = function($key) use ($config, $injectPassableData)
{
  if($config['inject'] && array_key_exists($key, $config['inject']))
  {
    if($config['inject'][$key])
    {
      return is_string($config['inject'][$key])
        ? $config['inject'][$key]
        : (is_callable($config['inject'][$key])
          ? $config['inject'][$key]($injectPassableData)
          : '');
    }
  }

  return '';
};

/**
 * Builds the header for the page
 *
 * @param Array     $config           Configuration values
 * @param Indexer   $indexer          Indexer class
 * @param String    $baseStylesheet   Base stylesheet
 * @param String    $currentTheme     Selected theme
 * @param Array     $themes           Themes array
 * @param Array     $metadata         Metadata array
 * @param String    $bust             Cache-busting string
 * @param String    $additionalCss    String of additional CSS
 * @param function  $getInjectable    Function to get injectable values
 * 
 * @return Array
 */ 
function buildHeader(
  $config,
  $indexer,
  $baseStylesheet,
  $currentTheme,
  $themes,
  $metadata,
  $bust,
  $additionalCss,
  $getInjectable
)
{
  /** Create header array and construct title */
  $header = [Helpers::createElement(
    'title', [], sprintf(
      $config['format']['title'],
      $indexer->getCurrentDirectory()
    )
  )];

  /** Construct metadata */
  foreach($metadata as &$meta)
  {
    $header[] = Helpers::createElement('meta', $meta);
  }

  /** Construct header icon */
  $header[] = Helpers::createElement('link', [
    'rel' => 'shortcut icon',
    'href' => $config['icon']['path'],
    'type' => $config['icon']['mime']
  ], NULL);

  /** Add base stylesheet link */
  $header[] = $baseStylesheet;

  /** Add current theme stylesheet */
  if($currentTheme && strtolower($currentTheme) !== 'default'
    && isset($themes[$currentTheme]))
  {
    $header[] = Helpers::createElement('link', [
      'rel' => 'stylesheet',
      'type' => 'text/css',
      'href' => sprintf(
        '%s?bust=%s', $themes[$currentTheme]['path'], $bust
      )
    ]);
  }

  /** Construct script linking */
  $header[] = Helpers::createElement('script', [
    'type' => 'text/javascript',
    'defer' => NULL,
    'src' => sprintf('<%= indexerPath %>main.js?bust=%s', $bust)
  ]);

  /** Additional stylesheets */
  if(!empty($additionalCss))
  {
    $header[] = Helpers::createElement('style', [
      'type' => 'text/css'
    ],  $additionalCss);
  }

  /** Injectable headers */
  $additionalHeaders = $getInjectable('head');

  if($additionalHeaders)
  {
    $header[] = $additionalHeaders;
  }

  return $header;
}

/** Server name constructor */
function constructServerNameNotice()
{
  return !empty($_SERVER['SERVER_NAME']) ? sprintf(
    ' @ %s', Helpers::createElement('a', [
      'href' => '/'
    ], $_SERVER['SERVER_NAME'])
  ) : '';
}

/**
 * Builds the footer for the page
 *
 * @param Float     $renderTime       Render time
 * @param String    $currentDirectory Current directory
 * @param Array     $config           Configuration values
 * @param String    $version          Current version
 * 
 * @return String
 */ 
/**
 * Shows who is signed in, and offers a way to stop being signed in
 *
 * Without this the session could be created but never deliberately ended,
 * which is one of the things HTTP digest could not do either.
 *
 * @return String
 */
function constructSessionNotice()
{
  if(!isset($_SESSION['user'], $_SESSION['csrf']))
  {
    return '';
  }

  return Helpers::createElement('div', [
    'class' => 'sessionInfo'
  ], sprintf(
    'Signed in as %s%s',
    Helpers::createElement('span', [], $_SESSION['user']),
    Helpers::createElement('a', [
      /* Carries the session token, so a link from elsewhere cannot sign you out */
      'href' => sprintf('?%s=%s', AUTH_PARAM_LOGOUT, rawurlencode($_SESSION['csrf'])),
      'class' => 'signOut'
    ], 'Sign out')
  ), true);
}

function constructFooter($renderTime, $currentDirectory, $config, $version)
{
  $footerHtml = [
    Helpers::createElement('div', [
      'class' => 'currentPageInfo'
    ], sprintf('Page generated in %s', Helpers::createElement('span', [
      'class' => 'generationTime'
    ], sprintf("%.6f", $renderTime) . 's')), true)
  ];

  $footerHtml[] = Helpers::createElement('div', [], sprintf(
    'Browsing %s%s', Helpers::createElement(
      'span', [], $currentDirectory
    ), constructServerNameNotice()
  ), true);

  if($config['credits'] !== false)
  {
    $footerHtml[] = Helpers::createElement('div', [
      'class' => 'referenceGit'
    ], implode('',  [
      Helpers::createElement('a', [
        'target' => '_blank',
        'href' => 'https://git.five.sh/ivfi/'
      ], 'IVFi'),
      Helpers::createElement('span', [], $version)
    ]), true);
  }

  $footerHtml[] = constructSessionNotice();

  return sprintf(
    '<div class="bottom">%s</div>', implode('', $footerHtml)
  );
}

/**
 * Creates the top-bar file/directory counts
 *
 * @param Integer  $modified   Most recently modified item
 * @param Integer  $count      Item count
 * @param String   $sString    Singular string
 * @param String   $pString    Plural string
 * 
 * @return String
 */ 
function generateCountDiv($modified, $count, $sString, $pString)
{
  $attributes = ['data-count' => $pString];

  if($modified)
  {
    $attributes['data-raw'] = $modified;
  }

  return Helpers::createElement('div', $attributes, sprintf(
    '%s %s', $count, ($count === 1 ? $sString : $pString)
  ));
}

/**
 * Creates the JS config object
 *
 * @param Array     $config      Configuration values
 * @param Array     $sorting     Sorting settings
 * @param Integer   $timestamp   Timestamp
 * @param String    $bust        Cache-busting string
 * @param Array     $theme       An array containg a pool and a selected theme
 * 
 * @return String
 */ 
function constructJsConfig($config, $sorting, $timestamp, $bust, $theme)
{
  /**
   * [Extract themes and options values]
   * 
   * Using list deconstruction here would be better, but
   * it's not supported in PHP 7.0, and dropping support
   * for a single feature isn't really worth it.
   * 
   * If for some reason we drop support for it in the
   * future, this should then be changed to use list deconstruction:
   * 
   * @see https://www.php.net/manual/en/function.list.php#refsect1-function.list-changelog
   */

  $preview = $config['preview'];
  $gallery = $config['gallery'];
  $extensions = $config['extensions'];

  $themePool = $theme['pool'];
  $themeCurrent = $theme['current'];

  /** Construct JS configuration */
  $jsConfig = [
    'bust' => $bust,
    'singlePage' => $config['single_page'],
    'preview' => [
      'enabled' => $preview['enabled'],
      'hoverDelay' => $preview['hover_delay'],
      'cursorIndicator' => $preview['cursor_indicator'],
    ],
    'sorting' => [
      'enabled' => $sorting['enabled'],
      'types' => $sorting['types'],
      'sortBy' => strtolower($sorting['sort_by']),
      'order' => $sorting['order'] === SORT_ASC ? 'asc' : 'desc',
      'directorySizes' => $config['directory_sizes']['enabled']
    ],
    'gallery' => [
      'enabled' => $gallery['enabled'],
      'reverseOptions' => $gallery['reverse_options'],
      'scrollInterval' => $gallery['scroll_interval'],
      'listAlignment' => $gallery['list_alignment'],
      'fitContent' => $gallery['fit_content'],
      'imageSharpen' => $gallery['image_sharpen']
    ],
    'extensions' => [
      'image' => $extensions['image'],
      'video' => $extensions['video']
    ],
    'style' => [
      'themes' => [
        'path' => $config['style']['themes']['path'],
        'pool' => $themePool,
        'set' => $themeCurrent ? $themeCurrent : 'default'
      ],
      'compact' => $config['style']['compact']
    ],
    'format' => array_intersect_key(
      $config['format'], array_flip(['sizes', 'date', 'title'])
    ),
    'encodeAll' => $config['encode_all'],
    'performance' => $config['performance'],
    'timestamp' => $timestamp,
    'debug' => $config['debug'],
    'mobile' => false
  ];

  /** Return JSON-encoded configuration */
  return json_encode($jsConfig);
}

/** Set metadata behavior */
$metadataBehavior = $data['dotFile']['metadataBehavior'] ?? 'overwrite';
$metadataBehavior = is_string($metadataBehavior)
  && $metadataBehavior === 'replace' ? 'replace' : 'overwrite';

/** Merge metadata using config and potential dotfile contents */
if($metadataBehavior === 'replace'
  && isset($data['dotFile']['metadata'])
  && is_array($data['dotFile']['metadata']))
{
  /** Replace metadata */
  $metadata = $data['dotFile']['metadata'];
} else {
  /** Overwrite metadata */
  $metadata = Helpers::mergeMetadata(
    isset($config['metadata'])
      && is_array($config['metadata'])
        ? $config['metadata']
        : [],
    isset($data['dotFile']['metadata'])
      && is_array($data['dotFile']['metadata'])
        ? $data['dotFile']['metadata']
        : []
  );
}

/**
 * Set default metadata values
 * 
 * These can be overwritten by the dotfile or the config
 * 
 * Order of priorty: Dotfile > Config > Default
 */
if($metadataBehavior === 'overwrite')
{
  $metadata = Helpers::mergeMetadata([
    [
      'charset' => 'utf-8'
    ],
    [
      'name' => 'viewport',
      'content' => 'width=device-width, initial-scale=1'
    ]
  ], $metadata);
}

/** Build header */
$header = buildHeader(
  $config,
  $indexer,
  $baseStylesheet,
  $currentTheme,
  $themes,
  $metadata,
  $bust,
  $additionalCss,
  $getInjectable
);

/** Create JS configuration */
$jsConfig = constructJsConfig(
  $config, $sorting, $indexer->timestamp, $bust, [
    'pool' => $themes,
    'current' => $currentTheme
  ]
);
?>
<!DOCTYPE HTML>
<html lang="en">
  <head>
    <?=implode(PHP_EOL . '    ', $header) . PHP_EOL;?>
  </head>

  <body class="rootDirectory<?=$compact ? ' compact' : ''?><?=!$footer['enabled'] ? ' pb' : ''?>" is-loading<?=$config['performance'] ? ' optimize' : '';?> root>
    <?=$getInjectable('body');?>
    <div class="topBar">
        <div class="extend">&#9881;</div>
        <div class="directoryInfo">
          <div data-count="size"><?=$data['size']['readable'];?></div>
          <?=generateCountDiv(
            $data['recent']['file'], $counts['files'], 'file', 'files'
          ) . PHP_EOL;?>
          <?=generateCountDiv(
            $data['recent']['directory'], $counts['directories'], 'directory', 'directories'
          );?>
        </div>
    </div>

    <div class="path">Index of <?=$indexer->makePathClickable($indexer->getCurrentDirectory());?></div>
    <%= buildInject.readmeSupport &&
      buildInject.readmeSupport.DISPLAY_SNIPPET ?
      buildInject.readmeSupport.DISPLAY_SNIPPET : null %>

    <div class="tableContainer">
      <table>
      <thead>
        <tr>
          <th>
            <span sortable="true" title="Sort by filename">Filename</span>
            <span class="sortingIndicator"></span>
          </th>

          <th>
            <span sortable="true" title="Sort by modification date">Modified</span>
            <span class="sortingIndicator"></span>
          </th>

          <th>
            <span sortable="true" title="Sort by filesize">Size</span>
            <span class="sortingIndicator"></span>
          </th>

          <th>
            <span sortable="true" title="Sort by filetype">Type</span>
            <span class="sortingIndicator"></span>
          </th>
        </tr>
      </thead>

      <?=$table['contents'];?>

      </table>
    </div>

    <?=$config['footer']['enabled'] ? (constructFooter(
      (microtime(true) - $render), $indexer->getCurrentDirectory(), $config, $version)
    ) : '';?>

    <div class="filterContainer" style="display: none;">
        <input type="text" placeholder="Search .." value="">
    </div>

    <!-- [https://git.five.sh/ivfi/ — The image and video friendly indexer]  -->  

    <script id="<?=SCRIPT_ID;?>" type="application/json"><?=$jsConfig;?></script>

    <script type="text/javascript">function getScrollbarWidth(){const e=document.createElement("div");e.style.visibility="hidden",e.style.overflow="scroll",e.style.msOverflowStyle="scrollbar",document.body.appendChild(e);const t=document.createElement("div");e.appendChild(t);const l=e.offsetWidth-t.offsetWidth;return e.parentNode.removeChild(e),l};document.documentElement.style.setProperty('--scrollbar-width', getScrollbarWidth() + 'px');</script>
    <?=$getInjectable('footer');?>
  </body>
</html>