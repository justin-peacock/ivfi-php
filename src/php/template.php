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
     * Upload options
     *
     * Lets a signed-in client drag files onto the listing to write them into
     * the directory being viewed.
     *
     * The endpoint only ever exists for a request that carries an
     * authenticated session, so it follows `authentication` above: a
     * deployment with no users configured, or one whose `restrict` pattern
     * does not cover the path, offers no upload at all. There is no flag that
     * changes that. Writing into a directory the web server serves is how a
     * listing becomes a shell, so it is not opened to anonymous visitors
     *
     *   'upload' => [
     *     'enabled' => true,
     *     'extensions' => ['jpg', 'png', 'mp4'], // true = the media extensions below
     *     'max_size' => 104857600,               // bytes, false follows php.ini
     *     'overwrite' => false,
     *     'directories' => true,                 // allow creating folders too
     *     'delete' => false,                     // allow deleting files and empty folders
     *     'restrict' => '/^\/(incoming)\/?/i'   // optional, only these paths
     *   ]
     */
    'upload' => [
      /* Whether uploads should be accepted at all */
      'enabled' => false,
      /**
       * The extensions that are accepted, as an allowlist.
       *
       * `true` accepts whatever `extensions` further down lists as image or
       * video. Extensions the server is liable to execute are refused
       * whatever this is set to
       */
      'extensions' => true,
      /* Largest accepted file in bytes. `false` follows the php.ini limits */
      'max_size' => false,
      /* Whether an upload may replace a file that is already there */
      'overwrite' => false,
      /**
       * Whether directories may be created as well as files uploaded.
       *
       * Follows the same gate as an upload: it writes into the served tree,
       * so it is offered to the same clients on the same paths
       */
      'directories' => true,
      /**
       * Whether files and empty folders may be deleted.
       *
       * Off unless asked for, because it is the one write that cannot be
       * undone. Same gate as an upload otherwise
       */
      'delete' => false,
      /* Optional pattern, so only some of the authenticated paths accept uploads */
      'restrict' => false
    ],
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
          'path' => '<%= indexerPath %>themes/',
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
   * The markup of a bundled icon
   *
   * Lucide icons (https://lucide.dev), ISC License, Copyright (c) 2026 Lucide
   * Icons and Contributors. The same shapes as `helpers/icons.ts` on the
   * client. Only fixed markup is returned, so it is safe to emit raw.
   *
   * Hidden from assistive technology and from the pointer: the element it sits
   * in carries the meaning, and the client's click handlers compare
   * `event.target` against the elements they bound
   *
   * @param String  $name  Icon name
   *
   * @return String  An empty string for an unknown name
   */
  public static function icon($name)
  {
    static $shapes = [
    'menu' => '<path d="M4 5h16"/><path d="M4 12h16"/><path d="M4 19h16"/>',
    'folder' => '<path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z"/>',
    'file' => '<path d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.704.706l3.588 3.588A2.4 2.4 0 0 1 20 8v12a2 2 0 0 1-2 2z"/><path d="M14 2v5a1 1 0 0 0 1 1h5"/>',
    'file-image' => '<path d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.704.706l3.588 3.588A2.4 2.4 0 0 1 20 8v12a2 2 0 0 1-2 2z"/><path d="M14 2v5a1 1 0 0 0 1 1h5"/><circle cx="10" cy="12" r="2"/><path d="m20 17-1.296-1.296a2.41 2.41 0 0 0-3.408 0L9 22"/>',
    'file-video' => '<path d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.704.706l3.588 3.588A2.4 2.4 0 0 1 20 8v12a2 2 0 0 1-2 2z"/><path d="M14 2v5a1 1 0 0 0 1 1h5"/><path d="M15.033 13.44a.647.647 0 0 1 0 1.12l-4.065 2.352a.645.645 0 0 1-.968-.56v-4.704a.645.645 0 0 1 .967-.56z"/>',
    'corner-left-up' => '<path d="M14 9 9 4 4 9"/><path d="M20 20h-7a4 4 0 0 1-4-4V4"/>',
    'download' => '<path d="M12 15V3"/><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/>',
    'log-out' => '<path d="m16 17 5-5-5-5"/><path d="M21 12H9"/><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>',
    ];

    if(!isset($shapes[$name]))
    {
      return '';
    }

    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"'
      . ' stroke="currentColor" stroke-width="2" stroke-linecap="round"'
      . ' stroke-linejoin="round" aria-hidden="true" class="icon icon-' . $name . '">'
      . $shapes[$name]
      . '</svg>';
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
/** Field names used by the upload endpoint */
define('UPLOAD_FIELD_ACTION', 'ivfi_action');
define('UPLOAD_FIELD_FILE', 'ivfi_file');
define('UPLOAD_FIELD_NAME', 'ivfi_name');
/** Values of `UPLOAD_FIELD_ACTION` that mark what a request is asking for */
define('UPLOAD_ACTION', 'upload');
define('UPLOAD_ACTION_DIRECTORY', 'directory');
define('UPLOAD_ACTION_DELETE', 'delete');
/**
 * Room left for the rest of a multipart body when deriving a size limit from
 * `post_max_size`: the boundaries, the part headers and the other fields
 */
define('UPLOAD_ENVELOPE_BYTES', 8192);
/**
 * Extensions that are never accepted, whatever the allowlist is set to.
 *
 * The script writes into a directory the web server is already serving, so an
 * accepted `.php` is not a file in a listing, it is code the next request
 * runs. The same goes for anything that reconfigures the server (`.htaccess`,
 * `.user.ini`) or that a stock handler mapping hands to an interpreter
 */
define('UPLOAD_BLOCKED_EXTENSIONS', [
  'php', 'php2', 'php3', 'php4', 'php5', 'php6', 'php7', 'php8',
  'phps', 'pht', 'phtm', 'phtml', 'phar', 'inc', 'hphp', 'ctp',
  'htaccess', 'htpasswd', 'ini', 'user',
  'cgi', 'fcgi', 'pl', 'py', 'rb', 'sh', 'bash',
  'asp', 'aspx', 'ashx', 'asmx', 'cer', 'jsp', 'jspx', 'shtml', 'shtm'
]);
/**
 * Extensions that are never accepted because of what the *browser* does with
 * them, rather than the server.
 *
 * The listing links every file directly, and one of these opened as a document
 * runs script in this page's origin: the origin holding the session cookie of
 * whoever opens it. An SVG is the one that matters in practice, because it is
 * an image everywhere else and is in the default media extensions, so without
 * this an upload allowlist of "images and video" quietly accepts markup that
 * can act as the next visitor
 */
define('UPLOAD_ACTIVE_EXTENSIONS', [
  'svg', 'svgz', 'html', 'htm', 'xhtml', 'xht', 'mhtml', 'mht',
  'xml', 'xsl', 'xslt', 'swf'
]);

/**
 * The first value of a possibly chained forwarding header
 *
 * Two proxies in a row produce a comma separated list such as
 * `https, http`, where the leftmost entry is what the original client used.
 *
 * @param String  $name  The $_SERVER key to read
 *
 * @return String
 */
function authForwardedValue($name)
{
  if(!isset($_SERVER[$name]))
  {
    return '';
  }

  $parts = explode(',', (string) $_SERVER[$name]);

  return trim($parts[0]);
}

/**
 * Whether the request reached us over HTTPS
 *
 * Used to decide whether the session cookie carries the `Secure` flag, so
 * getting it wrong either leaks the cookie over plaintext or makes login
 * silently impossible.
 *
 * @param Array  $options  Authentication options
 *
 * @return Boolean
 */
function authIsSecureRequest($options)
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
  if(!empty($options['behind_proxy']))
  {
    return strtolower(authForwardedValue('HTTP_X_FORWARDED_PROTO')) === 'https';
  }

  return false;
}

/**
 * The address the request came from
 *
 * Behind a proxy `REMOTE_ADDR` is the proxy, identical for every visitor, so
 * a counter keyed on it would treat the whole internet as one client: five
 * bad attempts from anybody would lock out everybody. The forwarded address
 * is only read when the operator has said a proxy is in front, and is only
 * trustworthy because such a proxy overwrites whatever the client sent.
 *
 * @param Array  $options  Authentication options
 *
 * @return String
 */
function authClientAddress($options)
{
  $fallback = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '-';

  if(empty($options['behind_proxy']))
  {
    return $fallback;
  }

  /**
   * Cloudflare and most proxies disagree on the header name, so it is
   * configurable. `CF-Connecting-IP` is the right choice behind Cloudflare
   */
  $header = (isset($options['client_ip_header']) && $options['client_ip_header'])
    ? $options['client_ip_header']
    : 'X-Forwarded-For';

  $key = 'HTTP_' . strtoupper(str_replace('-', '_', $header));
  $address = authForwardedValue($key);

  return $address !== '' ? $address : $fallback;
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

  /**
   * The name is derived from this installation rather than fixed. A fixed
   * name in a shared temp directory can be created in advance by any other
   * local user, mode 000, after which the counter can never be opened and
   * throttling is silently off for good. It also stops two installations on
   * one host from overwriting each other
   */
  $identity = substr(hash('sha256', __FILE__), 0, 16);

  return rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR
    . sprintf('ivfi-auth-%s.json', $identity);
}

/**
 * Identifies the source of an attempt without storing who made it
 *
 * Keyed on the address alone, deliberately. Including the username would let
 * one address try a common password against a list of names without ever
 * tripping a lockout, since each name would carry its own count.
 *
 * @param Array  $options  Authentication options
 *
 * @return String
 */
function authThrottleKey($options)
{
  return hash('sha256', authClientAddress($options));
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
  $key = authThrottleKey($options);
  $now = time();

  $existed = file_exists($path);
  $handle = @fopen($path, 'c+');

  if($handle === false)
  {
    /**
     * Without somewhere to record attempts there is no throttling. Say so
     * rather than failing the request, but make it visible: an unthrottled
     * login is a real weakening and should not pass unnoticed
     */
    static $warned = false;

    if(!$warned)
    {
      $warned = true;

      error_log(sprintf(
        'IVFi: cannot write the login throttle file at %s, attempts are not being limited', $path
      ));
    }

    return 0;
  }

  /**
   * Counts are nobody else's business, and the file often sits in a shared
   * directory. Checked every time rather than only on creation, so a file
   * left behind by an earlier version does not stay readable
   */
  if(!$existed || (fileperms($path) & 0777) !== 0600)
  {
    @chmod($path, 0600);
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
      /*
       * The shadcn tokens from the main stylesheet, repeated here because this
       * page deliberately loads nothing external. Light, or dark when the
       * system asks for it
       */
      :root {
        color-scheme: light dark;
        --background: oklch(1 0 0);
        --foreground: oklch(0.141 0.005 285.823);
        --card: oklch(1 0 0);
        --muted-foreground: oklch(0.552 0.016 285.938);
        --primary: oklch(0.841 0.238 128.85);
        --primary-foreground: oklch(0.405 0.101 131.063);
        --destructive: oklch(0.577 0.245 27.325);
        --input: oklch(0.92 0.004 286.32);
        --ring: oklch(0.705 0.015 286.067);
        --ring-edge: oklch(0.141 0.005 285.823 / 10%);
      }
      @media (prefers-color-scheme: dark) {
        :root {
          --background: oklch(0.141 0.005 285.823);
          --foreground: oklch(0.985 0 0);
          --card: oklch(0.21 0.006 285.885);
          --muted-foreground: oklch(0.705 0.015 286.067);
          --primary: oklch(0.768 0.233 130.85);
          --destructive: oklch(0.704 0.191 22.216);
          --input: oklch(1 0 0 / 15%);
          --ring: oklch(0.552 0.016 285.938);
          --ring-edge: oklch(0.985 0 0 / 10%);
        }
      }
      *, *::before, *::after { box-sizing: border-box; }
      body {
        margin: 0; min-height: 100vh; display: flex; padding: 16px;
        align-items: center; justify-content: center;
        background: var(--background); color: var(--foreground);
        font: 14px/1.5 ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        -webkit-font-smoothing: antialiased;
      }
      form {
        width: 100%; max-width: 360px; padding: 24px;
        background: var(--card); border-radius: 14px;
        box-shadow: 0 0 0 1px var(--ring-edge), 0 1px 2px rgb(0 0 0 / 5%);
        display: flex; flex-direction: column; gap: 16px;
      }
      h1 { margin: 0; font-size: 16px; font-weight: 500; line-height: 1.25; }
      label { display: flex; flex-direction: column; gap: 6px; font-size: 14px; font-weight: 500; }
      input[type=text], input[type=password] {
        height: 36px; width: 100%; padding: 4px 10px; font: inherit; font-weight: 400;
        color: var(--foreground); background: transparent;
        border: 1px solid var(--input); border-radius: 10px; outline: none;
        transition: border-color .15s, box-shadow .15s;
      }
      input[type=text]:focus-visible, input[type=password]:focus-visible {
        border-color: var(--ring);
        box-shadow: 0 0 0 3px color-mix(in oklch, var(--ring) 50%, transparent);
      }
      button {
        height: 36px; padding: 0 12px; font: inherit; font-weight: 500; cursor: pointer;
        color: var(--primary-foreground); background: var(--primary);
        border: 0; border-radius: 10px; transition: background-color .15s;
      }
      button:hover { background: color-mix(in oklch, var(--primary) 80%, transparent); }
      button:focus-visible { outline: none; box-shadow: 0 0 0 3px color-mix(in oklch, var(--ring) 50%, transparent); }
      .error {
        margin: 0; padding: 8px 12px; font-size: 13px; border-radius: 10px;
        color: var(--destructive);
        background: color-mix(in oklch, var(--destructive) 10%, transparent);
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
  /** Option keys that belong beside `users`, not inside it */
  $options = [
    'restrict' => 1, 'behind_proxy' => 1, 'throttle_path' => 1, 'client_ip_header' => 1
  ];

  foreach($users as $name => $credential)
  {
    if(is_string($credential) && !empty(password_get_info($credential)['algo']))
    {
      /**
       * A hash is a hash whatever the key is called. Nothing stops somebody
       * having a user named `restrict`, and under the nested form that is not
       * ambiguous at all
       */
      continue;
    }

    /**
     * With the flat form the option keys share the array with the users, so a
     * key that names an option and does not hold a hash is almost certainly a
     * misplaced option. Say which one, rather than reporting a password hash
     * problem that points nowhere near the actual mistake
     */
    if(isset($options[$name]))
    {
      error_log(sprintf(
        'IVFi: \'%s\' is an authentication option, not a user. Move the ' .
        'credentials under a \'users\' key so the options sit beside them.', $name
      ));

      return false;
    }

    error_log(sprintf(
      'IVFi: the credential for \'%s\' is not a password_hash() value.', $name
    ));

    return false;
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

  /**
   * Close any session that was already running before taking over.
   *
   * `session_set_cookie_params()` and `session_name()` only work before a
   * session exists. Called while one is active (session.auto_start, or an
   * earlier session_start) they warn and return false, and the cookie keeps
   * PHP's defaults: no HttpOnly, no SameSite, no Secure, and the wrong name.
   * Skipping them in that case would avoid the warning but leave every
   * protection here switched off, so close it and start our own instead
   */
  if(session_status() === PHP_SESSION_ACTIVE)
  {
    session_write_close();
  }

  if(session_status() !== PHP_SESSION_ACTIVE)
  {
    $params = [
      'lifetime' => 0,
      'path' => '/',
      'domain' => '',
      'secure' => authIsSecureRequest($options),
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
    session_start();
  }

  /* Signing out is a state change, so it carries the same token as the form */
  if(isset($_GET[AUTH_PARAM_LOGOUT]))
  {
    if(isset($_SESSION['logout'])
      && hash_equals($_SESSION['logout'], (string) $_GET[AUTH_PARAM_LOGOUT]))
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
   * Verify against a real configured hash when the user is unknown, so that
   * a missing account takes the same time as a wrong password and cannot be
   * told apart.
   *
   * It has to be one of the configured hashes rather than a literal: a
   * hardcoded bcrypt cost 12 against credentials generated with
   * PASSWORD_DEFAULT, which is cost 10 before PHP 8.4, would make an unknown
   * username take roughly four times as long as a wrong password. That is a
   * clearer signal than not equalising at all, and worse again under Argon2
   */
  $known = isset($users[$username]);
  $hash = $known ? $users[$username] : reset($users);

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

  /**
   * Separate from the form token, because this one is rendered into a link
   * and so reaches browser history, access logs and any Referer. Sharing one
   * value would put the token guarding the login form in all of those too
   */
  $_SESSION['logout'] = bin2hex(random_bytes(32));

  /* Redirect so a refresh does not repost the form */
  header('Location: ' . authLocalTarget());

  exit;
}

/**
 * Whether an extension is refused whatever the allowlist says
 *
 * @param String  $extension  A lowercase extension, without the dot
 *
 * @return Boolean
 */
function uploadIsRefusedExtension($extension)
{
  return in_array($extension, UPLOAD_BLOCKED_EXTENSIONS, true)
    || in_array($extension, UPLOAD_ACTIVE_EXTENSIONS, true);
}

/**
 * Parses a php.ini size value such as `8M` or `1G` into bytes
 *
 * @param String  $value  The ini value
 *
 * @return Integer
 */
function uploadParseIniSize($value)
{
  $value = trim((string) $value);

  if($value === '')
  {
    return 0;
  }

  $bytes = (int) $value;
  $unit = strtolower($value[strlen($value) - 1]);

  /* Deliberate fall-through: each unit is the one below it multiplied again */
  switch($unit)
  {
    case 'g': $bytes *= 1024;
    case 'm': $bytes *= 1024;
    case 'k': $bytes *= 1024;
  }

  return $bytes;
}

/**
 * The largest upload this deployment accepts, in bytes
 *
 * PHP discards a body over `post_max_size` before the script is reached, so
 * the client is told the limit up front rather than discovering it as a
 * request that answers with nothing it can read.
 *
 * @param Array  $options  Upload options
 *
 * @return Integer  Zero when nothing imposes a limit
 */
function uploadMaxSize($options)
{
  $limits = [];

  $file = uploadParseIniSize(ini_get('upload_max_filesize'));
  $post = uploadParseIniSize(ini_get('post_max_size'));

  if($file > 0)
  {
    $limits[] = $file;
  }

  /* The file is only part of the body, so the envelope comes out of the same budget */
  if($post > 0)
  {
    $limits[] = $post - UPLOAD_ENVELOPE_BYTES;
  }

  if(isset($options['max_size'])
    && is_int($options['max_size'])
    && $options['max_size'] > 0)
  {
    $limits[] = $options['max_size'];
  }

  if($limits === [])
  {
    return 0;
  }

  $smallest = min($limits);

  /**
   * A `post_max_size` the envelope alone exhausts leaves nothing for a file.
   * Reported as one byte rather than as zero, because zero is what every
   * caller here reads as "nothing limits this" — returning it would turn the
   * most restrictive configuration there is into no limit at all
   */
  return $smallest > 0 ? $smallest : 1;
}

/**
 * The extensions the upload endpoint accepts
 *
 * An allowlist rather than a list of things to refuse: a new handler mapping
 * on the host adds to what is dangerous, it never adds to what was listed
 * here, so anything unanticipated lands on the safe side.
 *
 * @param Array  $options     Upload options
 * @param Array  $extensions  The configured media extensions
 *
 * @return Array
 */
function uploadAllowedExtensions($options, $extensions)
{
  $allowed = isset($options['extensions']) ? $options['extensions'] : true;

  /**
   * Whether this list was written by the operator. The media extensions are
   * not: they carry `svg`, which is refused below, and complaining about a
   * default on every request would be noise in the log rather than a finding
   */
  $configured = $allowed !== true;

  /* `true` follows whatever the index already treats as media */
  if($allowed === true)
  {
    $allowed = [];

    if(is_array($extensions))
    {
      foreach(['image', 'video'] as $type)
      {
        if(isset($extensions[$type]) && is_array($extensions[$type]))
        {
          $allowed = array_merge($allowed, $extensions[$type]);
        }
      }
    }
  }

  if(!is_array($allowed))
  {
    return [];
  }

  $pool = [];

  foreach($allowed as $extension)
  {
    if(!is_string($extension))
    {
      continue;
    }

    $extension = strtolower(ltrim(trim($extension), '.'));

    if($extension === '')
    {
      continue;
    }

    /**
     * The blocklists win over the configuration. An operator who lists `php`
     * here has written an upload form for a web shell, and the likeliest way
     * for that to happen is a list copied from somewhere else
     */
    if(uploadIsRefusedExtension($extension))
    {
      if($configured)
      {
        error_log(sprintf(
          'IVFi: refusing to accept .%s uploads, which can run as this origin',
          $extension
        ));
      }

      continue;
    }

    $pool[$extension] = true;
  }

  return array_keys($pool);
}

/**
 * Reduces a client supplied filename to something safe to write
 *
 * @param String  $name  The name as the client sent it
 *
 * @return String  An empty string when nothing usable is left
 */
function uploadSafeName($name)
{
  $name = (string) $name;

  /**
   * A name that is not valid UTF-8 is refused rather than repaired: it cannot
   * be rendered back into the listing, and every `preg_*` below would return
   * NULL on it and quietly turn the name into nothing
   */
  if($name === '' || !preg_match('//u', $name))
  {
    return '';
  }

  /* Both separators, so a Windows client cannot describe a path */
  $name = str_replace('\\', '/', $name);
  $slash = strrpos($name, '/');

  if($slash !== false)
  {
    $name = substr($name, $slash + 1);
  }

  /* Control characters, the NUL that truncates a path among them */
  $name = preg_replace('/[\x00-\x1F\x7F]+/u', '', $name);

  /**
   * Windows drops trailing dots and spaces when it opens a path, so a name
   * ending in one is a name that means something else on arrival
   */
  $name = rtrim($name, " \t.");

  /**
   * Leading dots make a dotfile (`.htaccess`, `.user.ini`, `.ivfi`), and they
   * are taken together with the whitespace rather than after it: trimming the
   * two in separate passes leaves ` .secret.jpg` as a dotfile, because the
   * leading character at the point the dots are stripped is a space
   */
  $name = ltrim($name, " \t.");

  if($name === '' || $name === '.' || $name === '..')
  {
    return '';
  }

  /**
   * Refused rather than shortened, because trimming a long name to fit can
   * take the extension off it or collide it with something already there
   */
  if(strlen($name) > 255)
  {
    return '';
  }

  return $name;
}

/**
 * Why a sanitised filename cannot be accepted
 *
 * @param String  $name     A name that has been through `uploadSafeName()`
 * @param Array   $allowed  Accepted extensions
 *
 * @return String  NULL when the name is fine
 */
function uploadNameRejection($name, $allowed)
{
  $segments = explode('.', strtolower($name));

  if(count($segments) < 2)
  {
    return 'Files without an extension are not accepted.';
  }

  $extension = array_pop($segments);

  if(!in_array($extension, $allowed, true))
  {
    return sprintf('.%s files are not accepted here.', $extension);
  }

  /* The first segment is the name itself, not an extension */
  array_shift($segments);

  /**
   * Apache's `AddHandler` matches any extension in a name rather than the last
   * one, so on a host configured that way `payload.php.jpg` is served as PHP
   * despite ending in something this allowlist accepts
   */
  foreach($segments as $segment)
  {
    if(uploadIsRefusedExtension($segment))
    {
      /**
       * Deliberately not "the server may execute": this covers the active
       * content list too, and an `.svg` is refused for what the browser does
       * with it rather than the server
       */
      return 'That name carries an extension that is not accepted here.';
    }
  }

  return NULL;
}

/**
 * A readable form of a `$_FILES` error code
 *
 * @param Integer  $code  The error code
 *
 * @return String
 */
function uploadErrorMessage($code)
{
  switch($code)
  {
    case UPLOAD_ERR_INI_SIZE:
      return sprintf(
        'The file is larger than the server accepts (upload_max_filesize is %s).',
        ini_get('upload_max_filesize')
      );

    case UPLOAD_ERR_FORM_SIZE:
      return 'The file is larger than this form accepts.';

    case UPLOAD_ERR_PARTIAL:
      return 'The upload was interrupted before it finished.';

    case UPLOAD_ERR_NO_FILE:
      return 'No file was sent.';

    case UPLOAD_ERR_NO_TMP_DIR:
    case UPLOAD_ERR_CANT_WRITE:
    case UPLOAD_ERR_EXTENSION:
      error_log(sprintf('IVFi: an upload failed server-side with code %d', $code));

      return 'The server could not store the upload.';
  }

  return 'The upload failed.';
}

/**
 * Claims a name for a staged file without overwriting anything
 *
 * @param String  $staged  The staged file
 * @param String  $target  The name to claim
 *
 * @return Boolean
 */
function uploadClaim($staged, $target)
{
  /**
   * Checked rather than called and caught. Shared hosts routinely put `link`
   * in `disable_functions`, and on PHP 8 a disabled function is gone from the
   * function table rather than returning false: calling it raises an `Error`
   * that `@` does not suppress, which would abandon the staged file and answer
   * with a 500 carrying no JSON at all. Those hosts are exactly the ones the
   * fallback below exists for
   */
  if(!function_exists('link'))
  {
    return false;
  }

  return @link($staged, $target);
}

/**
 * Answers an upload request, ending the request
 *
 * @param Integer  $status   HTTP status code
 * @param Array    $payload  The response body
 *
 * @return void
 */
function uploadRespond($status, $payload)
{
  http_response_code($status);

  header('Content-Type: application/json; charset=utf-8');
  header('Cache-Control: no-store');
  /* The answer is read by script, never rendered, so nothing should sniff it */
  header('X-Content-Type-Options: nosniff');

  exit(json_encode($payload));
}

/**
 * Whether this request may upload
 *
 * @param Array    $config         Configuration values
 * @param Boolean  $authenticated  Whether the request carries a signed-in session
 *
 * @return Boolean
 */
function uploadIsAvailable($config, $authenticated)
{
  if(!uploadIsEnabled($config))
  {
    return false;
  }

  /**
   * The endpoint writes into a directory the web server serves, so it only
   * exists for a request that has already proven who it is. A deployment with
   * no `authentication`, or one whose `restrict` pattern leaves this path
   * open, arrives here unauthenticated and is offered nothing
   */
  if(!$authenticated)
  {
    return false;
  }

  /* An optional second filter, for opening up only part of an index */
  if(isset($config['upload']['restrict'])
    && is_string($config['upload']['restrict']))
  {
    /**
     * A pattern that does not compile makes `preg_match()` return false, which
     * closes the endpoint rather than opening it
     */
    if(!preg_match($config['upload']['restrict'], CURRENT_URI))
    {
      return false;
    }
  }

  return true;
}

/**
 * Whether directories may be created
 *
 * @param Array    $config     Configuration values
 * @param Boolean  $available  Whether this request may write here at all
 *
 * @return Boolean
 */
function uploadDirectoriesAreAvailable($config, $available)
{
  return $available
    && (!isset($config['upload']['directories'])
      || !empty($config['upload']['directories']));
}

/**
 * Why a sanitised directory name cannot be used
 *
 * A directory carries no extension to check against an allowlist, so the rule
 * is the other way round to a file's: anything is acceptable except a name the
 * server treats as something other than a directory.
 *
 * @param String  $name  A name that has been through `uploadSafeName()`
 *
 * @return String  NULL when the name is fine
 */
function uploadDirectoryRejection($name)
{
  foreach(explode('.', strtolower($name)) as $index => $segment)
  {
    /* The part before the first dot is the name itself, not an extension */
    if($index === 0)
    {
      continue;
    }

    /**
     * A directory is not executed, but one named `reports.php` is still routed
     * to the interpreter by a typical handler mapping, which answers a request
     * to browse it with a 404 rather than a listing. Refused at the point it is
     * created, where it can still be given a different name.
     *
     * Every segment is checked, not only the last, for the same reason the
     * upload path checks them: a handler mapping can match any of them, so
     * `reports.php.stuff` is routed the same way `reports.php` is
     */
    if(uploadIsRefusedExtension($segment))
    {
      return 'That name carries an extension the server treats specially.';
    }
  }

  return NULL;
}

/**
 * Handles a request to create a directory, and returns otherwise
 *
 * @param Indexer  $indexer    Indexer class
 * @param Array    $config     Configuration values
 * @param Boolean  $available  Whether this request may write here
 *
 * @return void
 */
function handleDirectory($indexer, $config, $available)
{
  if(!uploadIsEnabled($config)
    || (isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET') !== 'POST'
    || !isset($_POST[UPLOAD_FIELD_ACTION])
    || $_POST[UPLOAD_FIELD_ACTION] !== UPLOAD_ACTION_DIRECTORY)
  {
    return;
  }

  if(!uploadDirectoriesAreAvailable($config, $available))
  {
    uploadRespond(403, [
      'ok' => false,
      'error' => 'Directories cannot be created here.'
    ]);
  }

  $token = isset($_POST[AUTH_FIELD_CSRF]) ? (string) $_POST[AUTH_FIELD_CSRF] : '';

  if(empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $token))
  {
    uploadRespond(403, [
      'ok' => false,
      'error' => 'Your session expired. Reload the page and try again.'
    ]);
  }

  $name = isset($_POST[UPLOAD_FIELD_NAME]) && is_string($_POST[UPLOAD_FIELD_NAME])
    ? uploadSafeName($_POST[UPLOAD_FIELD_NAME])
    : '';

  if($name === '')
  {
    uploadRespond(400, [
      'ok' => false,
      'error' => 'That name cannot be used.'
    ]);
  }

  $rejection = uploadDirectoryRejection($name);

  if($rejection !== NULL)
  {
    uploadRespond(400, ['ok' => false, 'error' => $rejection]);
  }

  $directory = $indexer->path;

  /* The same containment the upload path checks, for the same reason */
  if(!is_dir($directory)
    || !Helpers::isAboveCurrent($directory, BASE_PATH))
  {
    uploadRespond(403, [
      'ok' => false,
      'error' => 'That directory cannot be written to.'
    ]);
  }

  $directory = rtrim($directory, DIRECTORY_SEPARATOR);
  $target = $directory . DIRECTORY_SEPARATOR . $name;

  /* A sanitised name resolves inside the directory; anything else is a bug here */
  if(dirname($target) !== $directory)
  {
    uploadRespond(400, [
      'ok' => false,
      'error' => 'That name cannot be used.'
    ]);
  }

  if(!is_writable($directory))
  {
    error_log(sprintf('IVFi: cannot create a directory in %s, which is not writable', $directory));

    uploadRespond(500, [
      'ok' => false,
      'error' => 'The server cannot write to this directory.'
    ]);
  }

  /**
   * `mkdir()` is the whole operation: it creates the name or fails because
   * something already holds it, in one step. No staging or separate existence
   * check, which is what the file path needs only because a file arrives as
   * contents that have to be put somewhere first
   */
  if(!@mkdir($target, 0755))
  {
    if(file_exists($target) || is_link($target))
    {
      uploadRespond(409, [
        'ok' => false,
        'error' => 'Something with that name is already here.'
      ]);
    }

    error_log(sprintf('IVFi: could not create a directory in %s', $directory));

    uploadRespond(500, [
      'ok' => false,
      'error' => 'The directory could not be created.'
    ]);
  }

  uploadRespond(201, [
    'ok' => true,
    'directory' => ['name' => $name]
  ]);
}

/**
 * Whether files and empty directories may be deleted
 *
 * @param Array    $config     Configuration values
 * @param Boolean  $available  Whether this request may write here at all
 *
 * @return Boolean
 */
function uploadDeleteIsAvailable($config, $available)
{
  return $available && !empty($config['upload']['delete']);
}

/**
 * Handles a request to delete a file or an empty directory, and returns otherwise
 *
 * @param Indexer  $indexer    Indexer class
 * @param Array    $config     Configuration values
 * @param Boolean  $available  Whether this request may write here
 *
 * @return void
 */
function handleDelete($indexer, $config, $available)
{
  if(!uploadIsEnabled($config)
    || (isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET') !== 'POST'
    || !isset($_POST[UPLOAD_FIELD_ACTION])
    || $_POST[UPLOAD_FIELD_ACTION] !== UPLOAD_ACTION_DELETE)
  {
    return;
  }

  if(!uploadDeleteIsAvailable($config, $available))
  {
    uploadRespond(403, [
      'ok' => false,
      'error' => 'Nothing can be deleted here.'
    ]);
  }

  $token = isset($_POST[AUTH_FIELD_CSRF]) ? (string) $_POST[AUTH_FIELD_CSRF] : '';

  if(empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $token))
  {
    uploadRespond(403, [
      'ok' => false,
      'error' => 'Your session expired. Reload the page and try again.'
    ]);
  }

  /**
   * Taken exactly as sent, not through `uploadSafeName()`. Sanitising a name
   * that is about to be deleted changes which entry it names: `holiday.jpg.`
   * would delete `holiday.jpg`
   */
  $name = isset($_POST[UPLOAD_FIELD_NAME]) && is_string($_POST[UPLOAD_FIELD_NAME])
    ? $_POST[UPLOAD_FIELD_NAME]
    : '';

  $directory = $indexer->path;

  /* The same containment the other writes check, for the same reason */
  if(!is_dir($directory)
    || !Helpers::isAboveCurrent($directory, BASE_PATH))
  {
    uploadRespond(403, [
      'ok' => false,
      'error' => 'That directory cannot be written to.'
    ]);
  }

  /**
   * Only what the listing shows. Hidden entries stay out of reach, and so does
   * any name that is not exactly an entry here, a path among them
   */
  $type = $name === '' ? NULL : $indexer->listedType($name);

  if($type === NULL)
  {
    uploadRespond(404, [
      'ok' => false,
      'error' => 'That is not here any more. Reload the page.'
    ]);
  }

  $directory = rtrim($directory, DIRECTORY_SEPARATOR);
  $target = $directory . DIRECTORY_SEPARATOR . $name;

  /* An entry of this directory resolves inside it; anything else is a bug here */
  if(dirname($target) !== $directory)
  {
    uploadRespond(400, [
      'ok' => false,
      'error' => 'That name cannot be deleted.'
    ]);
  }

  /**
   * Nothing the server would run. In an install where the index is served from
   * the web root, that is the indexer's own configuration and anything else PHP
   * sitting beside it, which is not something a listing should be able to take
   * down. Every segment, the way uploads check them
   */
  if($type === 'file')
  {
    $segments = explode('.', strtolower($name));

    array_shift($segments);

    foreach($segments as $segment)
    {
      if(in_array($segment, UPLOAD_BLOCKED_EXTENSIONS, true))
      {
        uploadRespond(403, [
          'ok' => false,
          'error' => 'Files the server can run cannot be deleted from here.'
        ]);
      }
    }
  }

  if(!is_writable($directory))
  {
    error_log(sprintf('IVFi: cannot delete from %s, which is not writable', $directory));

    uploadRespond(500, [
      'ok' => false,
      'error' => 'The server cannot write to this directory.'
    ]);
  }

  /**
   * A link is removed as a link, never followed: `rmdir()` refuses one that
   * points at a directory, and `unlink()` on it leaves the target alone
   */
  if($type === 'directory' && !is_link($target))
  {
    /**
     * `rmdir()` only removes an empty directory, which is the whole rule. No
     * recursion and no separate emptiness check to race against: a file that
     * lands in between makes it fail rather than go with the directory
     */
    if(!@rmdir($target))
    {
      $entries = @scandir($target);

      if(is_array($entries) && count($entries) > 2)
      {
        uploadRespond(409, [
          'ok' => false,
          'error' => 'Only an empty folder can be deleted, and this one has something in it (hidden files count).'
        ]);
      }

      error_log(sprintf('IVFi: could not delete a directory in %s', $directory));

      uploadRespond(500, [
        'ok' => false,
        'error' => 'The folder could not be deleted.'
      ]);
    }
  } else if(!@unlink($target))
  {
    if(!file_exists($target) && !is_link($target))
    {
      uploadRespond(404, [
        'ok' => false,
        'error' => 'That is not here any more. Reload the page.'
      ]);
    }

    error_log(sprintf('IVFi: could not delete a file in %s', $directory));

    uploadRespond(500, [
      'ok' => false,
      'error' => 'The file could not be deleted.'
    ]);
  }

  uploadRespond(200, [
    'ok' => true,
    'deleted' => ['name' => $name, 'type' => $type]
  ]);
}

/**
 * Whether uploads are switched on at all, regardless of who is asking
 *
 * @param Array  $config  Configuration values
 *
 * @return Boolean
 */
function uploadIsEnabled($config)
{
  return isset($config['upload'])
    && is_array($config['upload'])
    && !empty($config['upload']['enabled']);
}

/**
 * Handles the request when it is an upload, and returns otherwise
 *
 * @param Indexer  $indexer    Indexer class
 * @param Array    $config     Configuration values
 * @param Boolean  $available  Whether this request may upload
 *
 * @return void
 */
function handleUpload($indexer, $config, $available)
{
  if(!uploadIsEnabled($config)
    || (isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET') !== 'POST')
  {
    return;
  }

  $contentType = isset($_SERVER['CONTENT_TYPE']) ? strtolower($_SERVER['CONTENT_TYPE']) : '';

  if(strpos($contentType, 'multipart/form-data') !== 0)
  {
    return;
  }

  /**
   * A body over `post_max_size` is thrown away before the script runs, leaving
   * a POST carrying neither fields nor files. Nothing in it says it was an
   * upload, so it is recognised by that shape: the alternative is rendering a
   * whole listing at a client that is parsing the answer as JSON
   */
  $discarded = $_POST === []
    && $_FILES === []
    && isset($_SERVER['CONTENT_LENGTH'])
    && (int) $_SERVER['CONTENT_LENGTH'] > 0;

  if(!$discarded
    && (!isset($_POST[UPLOAD_FIELD_ACTION])
      || $_POST[UPLOAD_FIELD_ACTION] !== UPLOAD_ACTION))
  {
    return;
  }

  if(!$available)
  {
    uploadRespond(403, [
      'ok' => false,
      'error' => 'Uploads are not enabled here.'
    ]);
  }

  if($discarded)
  {
    uploadRespond(413, [
      'ok' => false,
      'error' => sprintf(
        'The request was larger than the server accepts (post_max_size is %s).',
        ini_get('post_max_size')
      )
    ]);
  }

  $token = isset($_POST[AUTH_FIELD_CSRF]) ? (string) $_POST[AUTH_FIELD_CSRF] : '';

  if(empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $token))
  {
    uploadRespond(403, [
      'ok' => false,
      'error' => 'Your session expired. Reload the page and try again.'
    ]);
  }

  if(!isset($_FILES[UPLOAD_FIELD_FILE])
    || !is_array($_FILES[UPLOAD_FIELD_FILE])
    || is_array($_FILES[UPLOAD_FIELD_FILE]['name']))
  {
    uploadRespond(400, [
      'ok' => false,
      'error' => 'No file was sent.'
    ]);
  }

  $file = $_FILES[UPLOAD_FIELD_FILE];
  $code = isset($file['error']) ? (int) $file['error'] : UPLOAD_ERR_NO_FILE;

  if($code !== UPLOAD_ERR_OK)
  {
    uploadRespond(
      ($code === UPLOAD_ERR_INI_SIZE || $code === UPLOAD_ERR_FORM_SIZE) ? 413 : 400,
      ['ok' => false, 'error' => uploadErrorMessage($code)]
    );
  }

  /**
   * Guards against a `tmp_name` that names a path the request never uploaded,
   * which is what turns a move into a way of relocating any readable file
   */
  if(!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name']))
  {
    uploadRespond(400, [
      'ok' => false,
      'error' => 'The upload did not arrive intact.'
    ]);
  }

  $name = uploadSafeName(isset($file['name']) ? $file['name'] : '');

  if($name === '')
  {
    uploadRespond(400, [
      'ok' => false,
      'error' => 'That filename cannot be used.'
    ]);
  }

  $rejection = uploadNameRejection(
    $name, uploadAllowedExtensions($config['upload'], $config['extensions'])
  );

  if($rejection !== NULL)
  {
    uploadRespond(415, ['ok' => false, 'error' => $rejection]);
  }

  $maximum = uploadMaxSize($config['upload']);
  $size = isset($file['size']) ? (int) $file['size'] : 0;

  if($maximum > 0 && $size > $maximum)
  {
    uploadRespond(413, [
      'ok' => false,
      'error' => 'The file is larger than this server accepts.'
    ]);
  }

  $directory = $indexer->path;

  /**
   * The constructor already refused a path outside the base directory. It is
   * checked again because this is the one request that writes rather than
   * lists, and the cost of being wrong is not a wrong listing
   */
  if(!is_dir($directory)
    || !Helpers::isAboveCurrent($directory, BASE_PATH))
  {
    uploadRespond(403, [
      'ok' => false,
      'error' => 'That directory does not accept uploads.'
    ]);
  }

  $directory = rtrim($directory, DIRECTORY_SEPARATOR);
  $target = $directory . DIRECTORY_SEPARATOR . $name;

  /* A sanitised name resolves inside the directory; anything else is a bug here */
  if(dirname($target) !== $directory)
  {
    uploadRespond(400, [
      'ok' => false,
      'error' => 'That filename cannot be used.'
    ]);
  }

  $overwrite = !empty($config['upload']['overwrite']);

  /**
   * A cheap early answer, so the common case of a name already taken does not
   * pay for the move first. It is not what enforces the rule: that is the
   * `link()` below, because anything decided here can change before the write
   */
  if(file_exists($target) || is_link($target))
  {
    if(!$overwrite)
    {
      uploadRespond(409, [
        'ok' => false,
        'error' => 'A file with that name is already here.'
      ]);
    }

    /**
     * Overwriting is for replacing a file with a newer copy of itself. A
     * directory, or a symlink pointing at something outside this tree, is not
     * that, and following one would write wherever it happens to lead
     */
    if(!is_file($target) || is_link($target))
    {
      uploadRespond(409, [
        'ok' => false,
        'error' => 'That name is taken by something an upload cannot replace.'
      ]);
    }
  }

  if(!is_writable($directory))
  {
    error_log(sprintf('IVFi: uploads are enabled but %s is not writable', $directory));

    uploadRespond(500, [
      'ok' => false,
      'error' => 'The server cannot write to this directory.'
    ]);
  }

  /**
   * Staged under a name of this script's choosing, inside the directory it is
   * bound for, so that the step which claims the real name is a single
   * operation on one filesystem. Moving straight to the target instead would
   * leave a window between the checks above and the write in which another
   * request creates the file, or swaps a symlink in at the name:
   * `move_uploaded_file()` overwrites what it finds and follows where it
   * points.
   *
   * The staging name is a dotfile, which the listing skips, so a request
   * arriving mid-upload never sees a partial file
   */
  $staged = sprintf(
    '%s%s.ivfi-upload-%s.part',
    $directory, DIRECTORY_SEPARATOR, bin2hex(random_bytes(8))
  );

  if(!move_uploaded_file($file['tmp_name'], $staged))
  {
    error_log(sprintf('IVFi: could not move an upload into %s', $directory));

    uploadRespond(500, [
      'ok' => false,
      'error' => 'The file could not be written.'
    ]);
  }

  /**
   * `move_uploaded_file()` carries the temporary file's mode over, which
   * follows the process umask and can leave the file unreadable to the very
   * server that is meant to serve it back. Set here rather than after the
   * rename, so the file never sits at its real name with the wrong mode.
   *
   * A failure is not by itself fatal: filesystems that carry no Unix modes at
   * all (a FAT or CIFS mount, say) refuse every `chmod()` while serving the
   * file perfectly well, and discarding a good upload there would be worse
   * than the problem. What matters is the outcome, so that is what is checked
   */
  if(!@chmod($staged, 0644))
  {
    if(!is_readable($staged))
    {
      @unlink($staged);

      error_log(sprintf(
        'IVFi: an upload into %s could not be made readable', $directory
      ));

      uploadRespond(500, [
        'ok' => false,
        'error' => 'The file could not be stored readably.'
      ]);
    }

    error_log(sprintf(
      'IVFi: could not set the mode on an upload into %s, which is readable anyway',
      $directory
    ));
  }

  if($overwrite)
  {
    /**
     * `rename()` replaces the directory entry itself rather than writing
     * through it, so a symlink that appeared at the name since the check above
     * is replaced rather than followed
     */
    if(!@rename($staged, $target))
    {
      @unlink($staged);

      error_log(sprintf('IVFi: could not rename an upload into place in %s', $directory));

      uploadRespond(500, [
        'ok' => false,
        'error' => 'The file could not be written.'
      ]);
    }
  } else if(!uploadClaim($staged, $target))
  {
    /**
     * `link()` is the atomic claim on a name: it fails outright when anything
     * already holds it, a symlink or a directory included, which is what makes
     * "do not overwrite" a guarantee rather than the result of a check that
     * another request can invalidate
     */
    /**
     * The early check above stat'ed this path and PHP caches that per request,
     * so without clearing it the answer here can be the one from before the
     * upload was staged. A name another request claimed in between would read
     * as free, and the fallback below would rename straight over it
     */
    clearstatcache(true, $target);

    $taken = file_exists($target) || is_link($target);

    if(!$taken && @rename($staged, $target))
    {
      /**
       * Some filesystems do not carry hard links at all. Falling back keeps
       * those deployments working, at the cost of the guarantee narrowing back
       * to the window this rename occupies
       */
      error_log(sprintf(
        'IVFi: could not hard link into %s (unsupported, or `link` is disabled), '
          . 'so uploads there cannot claim a name atomically',
        $directory
      ));
    } else {
      @unlink($staged);

      if($taken)
      {
        uploadRespond(409, [
          'ok' => false,
          'error' => 'A file with that name is already here.'
        ]);
      }

      error_log(sprintf('IVFi: could not link an upload into place in %s', $directory));

      uploadRespond(500, [
        'ok' => false,
        'error' => 'The file could not be written.'
      ]);
    }
  } else {
    /* The link is the file now, so the staged name is just another reference */
    @unlink($staged);
  }

  uploadRespond(201, [
    'ok' => true,
    'file' => [
      'name' => $name,
      'size' => $size
    ]
  ]);
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
          /* Quoted, since a name such as `a(b` is not a valid expression */
          foreach(preg_grep('/^(' . preg_quote($item, '/') . '|index)\.css$/', scandir(
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
 * Default configuration values, used for anything a config file leaves unset.
 *
 * Taken from the literal above rather than kept as a second copy, which had
 * drifted: the copy defaulted the date to `m/d/y` and the icon to a `.png`
 * while the file and the documentation said `d/m/y` and `.ico`, so a config
 * file that left those out silently got different values from a bare install
 */
$defaults = $config;

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

/**
 * Whether this request carries a session `authenticate()` accepted.
 *
 * Set from the calls below rather than read back off `$_SESSION`, because the
 * session is not necessarily this script's: with `session.auto_start` on, any
 * other application sharing the host can leave a `user` key in there, and an
 * index with no `authentication` configured would then read it as a sign-in
 */
$authenticated = false;

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
      /* Returns only on an accepted session, and ends the request otherwise */
      authenticate(
        $config['authentication']['users'],
        'Restricted content.',
        $config['authentication']
      );

      $authenticated = true;
    }
  } else {
    /* Don't use any potential `users` array to authenticate, use main array instead */
    /**
     * The flat form is the credential map itself, so it carries no options.
     * Passing it as both means a stray option key is caught by the credential
     * check below and named, instead of being read as a username
     */
    authenticate(
      $config['authentication'], 'Restricted content.', $config['authentication']
    );

    $authenticated = true;
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
$bust = md5($config['debug'] ? time() : $version . '<%= buildId %>');

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
  /**
   * Collapsed as well as wrapped, because the client compares this value
   * against stylesheet URLs, which never carry a doubled slash. A value with
   * one matched nothing, so switching themes left the previous sheet in place
   */
  $config['style']['themes']['path'] = preg_replace('#/+#', '/', Helpers::stringWrap(
    $config['style']['themes']['path'], '/'
  ));
}

/**
 * The units are indexed by magnitude, so an empty or non-array value would
 * leave every size without a unit and warn on each one. Fall back to the
 * documented list rather than propagating that to the page and the client
 */
if(!is_array($config['format']['sizes']) || $config['format']['sizes'] === [])
{
  $config['format']['sizes'] = $defaults['format']['sizes'];
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

      /** Icon for the kind of file, ahead of its escaped name */
      $fileIcon = parent::icon(
        $fileType[0] === 'image' ? 'file-image' : ($fileType[0] === 'video' ? 'file-video' : 'file')
      );

      /** Create file name column */
      $tdFileName = parent::createElement('td', [
        'data-raw' => $fileName
      ], parent::createElement(
        'a', $anchorAttributes, $fileIcon . parent::escape($fileName), true
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

      /** Create save anchor, an icon labelled for assistive technology */
      $anchorSave = parent::createElement('a', [
        'href' => $fileUrl,
        'filename' => $fileName,
        'download' => '',
        'title' => 'Download',
        'aria-label' => 'Download ' . $fileName
      ], parent::icon('download'), true);

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
        ], parent::icon('folder') . parent::escape($dir[1]), true
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
        ], parent::icon('corner-left-up') . 'Parent directory', true), true)
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
   * What a name in the current directory is, as far as the listing is concerned
   *
   * Runs the scan and filters the listing itself uses, so a caller acting on a
   * name can only reach what a client was shown: not a dotfile, not something
   * a filter or `.ivfi` hides, and not the indexer's own files at the base.
   * The name has to match a directory entry exactly, which also means it
   * cannot describe a path
   *
   * @param String  $name  An entry name, as the client sent it
   *
   * @return String  `file` or `directory`, NULL when the listing does not show it
   */
  public function listedType($name)
  {
    $listed = $this->handleFiles(self::getFiles(), self::getCurrentDirectory() === '/');

    foreach(['directories' => 'directory', 'files' => 'file'] as $key => $type)
    {
      foreach($listed[$key] as $item)
      {
        if($item[1] === $name)
        {
          return $type;
        }
      }
    }

    return NULL;
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
    /* A failed `filesize()` arrives as false or -1, and neither is a size */
    if($bytes < 1)
    {
      return '0' . $this->format['sizes'][0];
    }

    $base = log($bytes, 1024);

    /**
     * Anything past the last configured unit is shown in that unit rather
     * than indexing past the end of the array
     */
    $floored = min((int) floor($base), count($this->format['sizes']) - 1);
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
if($validate && !empty($client['style']['compact']))
{
  $config['style']['compact'] = true;
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

/** Whether this request is allowed to write into the directory it is viewing */
$uploadAvailable = uploadIsAvailable($config, $authenticated);

/**
 * Answered here rather than earlier because the target directory is the one
 * the Indexer resolved: its constructor has already refused anything outside
 * the base directory, so an upload cannot reach a path a listing could not.
 *
 * Returns immediately unless this request is an upload, and never returns when
 * it is
 */
handleUpload($indexer, $config, $uploadAvailable);
handleDirectory($indexer, $config, $uploadAvailable);
handleDelete($indexer, $config, $uploadAvailable);

/**
 * The page carries the form token so that an upload can prove it came from
 * here. It is the same value the login form renders on the same page, so this
 * exposes nothing the document did not already hold
 */
if($uploadAvailable && empty($_SESSION['csrf']))
{
  $_SESSION['csrf'] = bin2hex(random_bytes(32));
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
    /**
     * The cookie is client input, so the value is only honoured when it names
     * a theme that exists. Anything else, including a non-string, used to be
     * passed straight to `strtolower()`, which is a fatal error on an array
     */
    $currentTheme = (is_string($client['style']['theme'])
      && isset($themes[strtolower($client['style']['theme'])]))
      ? strtolower($client['style']['theme'])
      : NULL;
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
  ? (bool) $client['style']['compact']
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
 * Shows who is signed in, and offers a way to stop being signed in
 *
 * Without this the session could be created but never deliberately ended,
 * which is one of the things HTTP digest could not do either.
 *
 * @return String
 */
function constructSessionNotice()
{
  if(!isset($_SESSION['user'], $_SESSION['logout']))
  {
    return '';
  }

  return Helpers::createElement('div', [
    'class' => 'sessionInfo'
  ], sprintf(
    'Signed in as %s%s',
    Helpers::createElement('span', [], $_SESSION['user']),
    Helpers::createElement('a', [
      /* Its own token, not the form one, since this value reaches logs and history */
      'href' => sprintf('?%s=%s', AUTH_PARAM_LOGOUT, rawurlencode($_SESSION['logout'])),
      'class' => 'signOut'
    ], 'Sign out')
  ), true);
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
 * @param Boolean   $upload      Whether this request may upload
 * 
 * @return String
 */ 
function constructJsConfig($config, $sorting, $timestamp, $bust, $theme, $upload)
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
    /**
     * Only described to a client that may actually use it. Everyone else is
     * told it is off, and is handed no token, no limit and no field names
     */
    'upload' => $upload ? [
      'enabled' => true,
      'token' => isset($_SESSION['csrf']) ? $_SESSION['csrf'] : '',
      'action' => UPLOAD_ACTION,
      'directories' => uploadDirectoriesAreAvailable($config, true),
      'directoryAction' => UPLOAD_ACTION_DIRECTORY,
      'delete' => uploadDeleteIsAvailable($config, true),
      'deleteAction' => UPLOAD_ACTION_DELETE,
      'fields' => [
        'action' => UPLOAD_FIELD_ACTION,
        'file' => UPLOAD_FIELD_FILE,
        'name' => UPLOAD_FIELD_NAME,
        'token' => AUTH_FIELD_CSRF
      ],
      'extensions' => uploadAllowedExtensions(
        $config['upload'], $config['extensions']
      ),
      /**
       * The non-overridable lists, so the client can turn away a name the
       * endpoint would refuse for an extension that is not the last one, such
       * as `payload.php.jpg`. Nothing here is a secret: it is a fixed list,
       * and the refusal happens server-side whether the client knows or not
       */
      'blocked' => array_values(array_merge(
        UPLOAD_BLOCKED_EXTENSIONS, UPLOAD_ACTIVE_EXTENSIONS
      )),
      /**
       * So the client can turn an oversized file away itself. A body past
       * `post_max_size` is discarded before the script runs, which otherwise
       * surfaces as an upload that fails with nothing useful to say
       */
      'maxSize' => uploadMaxSize($config['upload']),
      'overwrite' => !empty($config['upload']['overwrite'])
    ] : [
      'enabled' => false
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
  ], $uploadAvailable
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
        <button type="button" class="extend" title="Menu" aria-label="Menu" aria-haspopup="menu" aria-expanded="false"><?=Helpers::icon('menu');?></button>
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
        <input type="text" placeholder="Filter this directory" aria-label="Filter this directory" value="">
    </div>

    <!-- [https://git.five.sh/ivfi/ — The image and video friendly indexer]  -->  

    <script id="<?=SCRIPT_ID;?>" type="application/json"><?=$jsConfig;?></script>

    <script type="text/javascript">function getScrollbarWidth(){const e=document.createElement("div");e.style.visibility="hidden",e.style.overflow="scroll",e.style.msOverflowStyle="scrollbar",document.body.appendChild(e);const t=document.createElement("div");e.appendChild(t);const l=e.offsetWidth-t.offsetWidth;return e.parentNode.removeChild(e),l};document.documentElement.style.setProperty('--scrollbar-width', getScrollbarWidth() + 'px');</script>
    <?=$getInjectable('footer');?>
  </body>
</html>