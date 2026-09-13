<h1 align="center">Configuration</h1>

<br/>

Here is an overview of what each configurable option does.

These options can be found and changed at the top of the `indexer.php` file, or you can create a config file that makes updating a bit easier (see below).

*Some* of these settings can be changed by the client/user. These are only values for the script to use as defaults.

## Configuration File

You can edit the configuration in the file directly, but if you wish to keep a separate config file that does not reset between updates, then creating your own config file can be a good solution.

The script will look for a config file in the same directory as the script. If the file is named `indexer.php` (as it is by default), then it'll look for a file called `indexer.config.php`. If you rename the file to `something.php`, then it'll look for `something.config.php` and so on.

Any values that are not present in the config file, will be set to the default value. It is also worth noting that having a `.` in front of the config file (hidden file) **will also work**.

A basic example of a config file:

```php
<?php
return array(
    'authentication' => array(
        'users' => array(
            /* Not a real hash: generate one, see Authentication below */
            'username' => 'REPLACE WITH THE OUTPUT OF password_hash()'
        ),
        'restrict' => '/^\/(protected|secret|directory\/protected)\/?/i'
    ),
    'icon' => array(
        'path' => '/favicon.png',
        'mime' => 'image/png'
    ),
    'style' => array(
        'themes' => array(
            'path' => 'indexer/css/themes',
            'default' => false
        ),
        'compact' => true
    ),
    'gallery' => array(
        'image_sharpen' => true
    ),
    'debug' => true
);
?>
```

## Authentication
Key: **`authentication`**

Gates the index behind a sign-in form. Credentials are checked against a
`password_hash()` value, the session is held in a cookie, and there is a sign-out
link in the footer.

| Child key | Type | Value | Description |
|-----|------|---------|-------------|
| `users` | Array | `username => hash` | Valid users, where each value is a `password_hash()` output. Plaintext is refused. |
| `restrict` | String | `regex` | Applies authentication only to paths matching the expression. |
| `behind_proxy` | Bool | `false` | Trust forwarded headers for the client address and protocol. Enable this **only** behind a proxy that overwrites them on the way in. |
| `client_ip_header` | String | `X-Forwarded-For` | Which header carries the client address when `behind_proxy` is set. Use `CF-Connecting-IP` behind Cloudflare. |
| `throttle_path` | String | system temp dir | Directory holding the failed-attempt counter. Must be writable by the web server. The filename includes a hash of the script path, so two installations on one host do not share a counter. |

### Generating a credential

```
php -r 'echo password_hash("your password", PASSWORD_DEFAULT), "\n";'
```

Put the result in `users`. The algorithm is whatever your PHP treats as current
(bcrypt today, argon2 where configured), and the cost is PHP's default.

Plaintext passwords are **rejected**, not quietly accepted: the script logs how to
generate a hash and refuses every sign-in until one is configured. That is
deliberate, since the alternative fails silently and leaves the secret readable on
disk.

### What it does

- Session cookie `IVFISESS`, marked `HttpOnly`, `SameSite=Lax`, and `Secure` when the request arrived over HTTPS.
- A new session identifier on sign-in, so one fixed beforehand cannot be reused.
- A token on the form, and on the sign-out link, so neither can be triggered from another site.
- The same message for an unknown user and a wrong password, so the form cannot be used to enumerate accounts.
- Five failed attempts from an address lock it out for fifteen minutes, and the correct password is refused during the lockout too.
- The sign-out link carries its own token, separate from the form token, because it ends up in browser history and access logs.
- Sessions stop being accepted after twelve hours idle.

### Behind a reverse proxy

Set `behind_proxy` when a proxy terminates TLS or forwards to this script. Two
things depend on it.

Without it, `REMOTE_ADDR` is the **proxy** for every visitor, so the lockout
counter treats all traffic as one client: five bad attempts from anybody locks
out everybody for fifteen minutes, which an unauthenticated attacker can keep up
indefinitely. With it, the address comes from `client_ip_header` instead.

Also without it, the session cookie is not marked `Secure` on an HTTPS site,
because the script only sees the plaintext hop from the proxy.

> Only enable it behind a proxy that **overwrites** these headers. A client can
> send `X-Forwarded-For` itself, so trusting it on a directly reachable server
> lets a visitor pick their own throttle bucket and evade the lockout entirely.
> Cloudflare overwrites `CF-Connecting-IP`, which is why it is the right choice
> there.

### A note on strength

This is a reasonable gate, not a security boundary. It is one password between the
internet and your files, over whatever transport your server provides.

**Serve it over HTTPS.** Without it the session cookie is sent in the clear and
the `Secure` flag cannot be set.

Where you already run an identity provider, prefer it. Authentication at the proxy
(Cloudflare Access, Authelia, Traefik middleware) keeps unauthenticated requests
from reaching PHP at all, which is strictly better than anything this script can do
once the request has arrived.

> **If you serve HTML files from an indexed directory**, remember they run on the
> same origin as this page. Anything in such a file executes as first-party script
> and can act with the signed-in session. Host untrusted or third-party HTML on a
> separate hostname.

Example:
```php
<?php
return array(
    'authentication' => array(
        'users' => array(
            /* Not a real hash: generate one with the command above */
            'username' => 'REPLACE WITH THE OUTPUT OF password_hash()'
        ),
        'restrict' => '/^\/(protected|secret|directory\/protected)\/?/i'
    )
);
?>
```
This would apply authentication to `/protected/`, `/secret/` and `/directory/protected/`.

## Upload
Key: **`upload`**

Lets a signed-in client drag files onto the listing to write them into the
directory being viewed. Disabled by default.

| Child key | Type | Default | Description |
|-----|------|---------|-------------|
| `enabled` | Bool | `false` | Whether uploads are accepted at all. |
| `extensions` | Bool/Array | `true` | The accepted extensions, as an allowlist. `true` follows whatever [`extensions`](#extensions) lists as image or video. |
| `max_size` | Bool/Int | `false` | Largest accepted file in bytes. `false` follows the php.ini limits. |
| `overwrite` | Bool | `false` | Whether an upload may replace a file that is already there. |
| `directories` | Bool | `true` | Whether folders may be created as well as files uploaded. |
| `restrict` | Bool/String | `false` | Applies uploads only to paths matching the expression, the way `authentication`'s own `restrict` does. |

### Authentication is required

Uploads follow [`authentication`](#authentication): the endpoint only exists for
a request that already carries a signed-in session. An index with no users
configured, or one whose `restrict` pattern leaves a path open, offers no upload
on that path, and there is no flag that changes it.

That is not caution for its own sake. The script writes into a directory the web
server is already serving, so an accepted upload is not a file in a listing, it
is potentially the next request's code. An anonymous write endpoint on a PHP
host is a web shell waiting to be found.

### What is accepted

The extension check is an allowlist, and it is the last segment of the name that
has to be on it. Two things happen regardless of what you configure:

- Extensions the server is liable to execute (`.php` and its variants, `.phar`,
  `.cgi`, `.pl`, `.py`, `.sh`, `.asp`, `.jsp`, `.shtml`) and files that
  reconfigure it (`.htaccess`, `.user.ini`) are **always refused**, even if you
  list one in `extensions`. Listing one is logged and ignored.
- Formats the **browser** treats as active are refused on the same terms:
  `.svg`, `.html`, `.xhtml`, `.mhtml`, `.xml`, `.xsl`, `.swf`. The listing links
  every file directly, and one of these opened as a document runs script in this
  page's origin, which is the origin holding the session cookie of whoever opens
  it. `svg` matters most, because it is an image everywhere else and is in the
  default `extensions` list, so without this an allowlist of "images and video"
  would quietly accept markup. It is dropped from the default silently; only an
  `svg` you list yourself is logged.
- Every other extension in the name is checked too, so `payload.php.jpg` and
  `drawing.svg.jpg` are refused. Apache's `AddHandler` matches any extension in
  a name rather than the last one, and on a host configured that way such a file
  is served as PHP.

Beyond that: a leading dot is stripped, so an upload cannot create a dotfile; a
name that describes a path is reduced to its last segment, so it cannot climb
out of the directory; and a name that is not valid UTF-8 is refused rather than
repaired.

### Creating folders

A signed-in client can also create a folder in the directory it is viewing,
from the `[New] Folder` item in the menu. It follows the same gate as an upload
and is switched off with `'directories' => false`, which leaves uploading on.

The name goes through the same handling as an uploaded file's: reduced to one
path segment, stripped of control characters and of leading whitespace and
dots, and refused when it is not valid UTF-8 or is too long for the
filesystem.

Dots are otherwise fine (`v1.2.3 release` works), with one exception. A name
ending in an extension from the blocklists above is refused, because a
directory called `reports.php` is not executed but *is* handed to the
interpreter by a typical handler mapping, which answers a request to browse it
with a 404 rather than a listing. Refusing it at the point of creation is the
only moment it can still be given a different name.

### Size limits

One request per file, so PHP's own limits apply. `upload_max_filesize` and
`post_max_size` both cap what arrives, and the smaller of the two, `max_size`
included, is what the page tells the client so an oversized file is turned away
before it is sent rather than after.

The stock values are **2M** and **8M**, which is well under a video file. Raise
them in `php.ini` (and `client_max_body_size` in nginx) for uploads of any size:

```
upload_max_filesize = 2G
post_max_size = 2G
max_execution_time = 600
```

### Claiming a name

An upload is staged under a dotfile name inside the directory it is bound for,
then moved onto its real name in one operation: `link()` when `overwrite` is
off, which fails outright if anything already holds the name, and `rename()`
when it is on, which replaces the directory entry rather than writing through
it. Two uploads racing for one name therefore end with one refused rather than
one silently replaced, and a symlink appearing at the name is replaced rather
than followed.

On a filesystem with no hard links the no-overwrite path falls back to
`rename()` and logs that it did, which keeps those deployments working with the
guarantee narrowed to that one call.

### The directory has to be writable

The uploaded file is written by the web server's user, so that user needs write
permission on the directory being uploaded to. The file itself is then set to
`0644`, because the mode a temporary upload carries follows the process umask
and can otherwise leave the file unreadable to the server that has to serve it
back.

Example:
```php
<?php
return array(
    'authentication' => array(
        'users' => array(
            'username' => 'REPLACE WITH THE OUTPUT OF password_hash()'
        )
    ),
    'upload' => array(
        'enabled' => true,
        'extensions' => array('jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm'),
        'max_size' => 1073741824,
        'overwrite' => false
    )
);
?>
```

## Format
Key: **`format`**

| Child key | Type | Default | Description |
|-----|------|---------|-------------|
| `title` | String | `Index of %s` | Page title where  `%s` represents the current path.
| `date` | String/Array | `array('d/m/y H:i', 'd/m/y')` | Date format as per [datetime.format.php](https://www.php.net/manual/en/datetime.format.php#refsect1-datetime.format-parameters). Can be a string or an array. If it is an array then the first value will be shown on desktop devices and the second will be shown on mobile devices. It is a good idea to set a shorter mobile format because of the limited screen space.
| `sizes` | Array | `' B', ' KiB', ' MiB', ' GiB', ' TiB'` | Size formats for when displaying filesizes.

## Icon
Key: **`icon`**

| Child key | Type | Default | Description |
|-----|------|---------|-------------|
| `path` | String | `/favicon.ico` | Path to a favicon.
| `mime` | String | `image/x-icon` | Favicon [MIME type](https://developer.mozilla.org/en-US/docs/Web/HTTP/Basics_of_HTTP/MIME_types/Complete_list_of_MIME_types)

## Sorting
Key: **`sorting`**

Default sorting settings.

Once the client sorts the items themselves, then those settings will be active for them instead.

| Child key | Type | Default | Description |
|-----|------|---------|-------------|
| `enabled` | Bool | `false` | Enables a specific sorting order on page-load. If disabled, it'll use the default order used by [scandir](https://www.php.net/manual/en/function.scandir.php).
| `order` | Integer | `SORT_ASC` | Sorting order. `SORT_ASC` or `SORT_DESC`.
| `types` | Integer | `0` | What item types to sort.<br/>`0` = Both. `1` = Files only. `2` = Directories only.
| `sort_by` | String | `name` | What to sort by. Available options are `name`, `modified`, `type` and `size`.
| `use_mbstring` | Bool | `false` | Enables [mbstring](https://www.php.net/manual/en/book.mbstring.php). This will solve some sorting issues with cyrillic capital letters et cetera, but it'll require `mbstring` to be installed. Only affects server-side sorting.

## Gallery
Key: **`gallery`**

The gallery plugin will display a gallery of the images and videos inside the current path.

| Child key | Type | Default | Description |
|-----|------|---------|-------------|
| `enabled` | Bool | `true` | Whether the gallery plugin should be enabled or not.
| `reverse_options` | Bool | `false` | Whether gallery images should have reverse search options or not.
| `scroll_interval` | Integer | `50` | Adds a forced break between scroll events in the gallery (`ms`).
| `list_alignment` | Integer | `0` | Gallery list alignment where `0` is `right` and `1` is `left`.
| `fit_content` | Bool | `true` | Whether images and videos should be forced to fill the available screen space.
| `image_sharpen` | Bool | `false` | Attempts to disable browser blurriness on images.

## Preview
Key: **`preview`**

The preview plugin displays a preview of the image or video when hovering over the filename.

| Child key | Type | Default | Description |
|-----|------|---------|-------------|
| `enabled` | Bool | `true` | Whether the preview plugin should be enabled or not.
| `hover_delay` | Integer | `75` | Adds a delay (`ms`) before the preview is displayed.
| `cursor_indicator` | Bool | `true` | Displays a loading cursor while the preview is loading.

## Extensions
Key: **`extensions`**

This setting decides which extensions will be marked as `media`.

This means that the extensions included here will have previews and will be included in the gallery mode.

| Child key | Type | Default | Description |
|-----|------|---------|-------------|
| `image` | Array | `'jpg', 'jpeg', 'gif', 'png', 'ico', 'svg', 'bmp', 'webp'` | Extensions marked as `image`.
| `video` | Array | `'webm', 'mp4', 'ogv', 'ogg', 'mov'` | Extensions marked as `video`.

## Inject
Key: **`inject`**

Injects any HTML code into the document.

This can be useful if you want to add meta tags or an external script, for example.

| Child key | Type | Default | Description |
|-----|------|---------|-------------|
| `head` | String / Function | `NULL` | Injects the code into the &lt;head&gt; of the document.
| `body` | String / Function | `NULL` | Injects (by prepending) the code into the &lt;body&gt; of the document.
| `footer` | String / Function | `NULL` | Injects (by appending) the code into the &lt;body&gt; of the document.

If the value is a function, then the returned string of that function will be used. The function will also be called with an argument containing an array with data that can be useful:
* `$param['path']` contains the currently shown path (example: `/files/directory/`)

* `$param['counts']` contains two keys — `files` and `directories` — both showing the respective file and directory count of the current directory.

* `$param['size']` contains two keys — `total` and `readable` — both showing the total size of the directory. The `total` value contains the total bytes and the `readable` value contains the readable size.

* `$param['config']` contains the parsed and active configuration.

## Metadata
Key: **`metadata`**

This option will add metadata to the header. This can be set globally using this option, or you can also set it on a per-directory basis using [dotfiles](dotfile.md).

Example:
```php
<?php
    return array(
        'metadata' => [
            [
                'property' => 'og:title',
                'content' => 'This is a title!'
            ]
	    ],
    );
?>
```

## Style
Key: **`style`**

Various visual options for the script.

The `compact` setting can be changed by the client in the settings menu, as can `themes`, if they are enabled.

| Child key | Type | Default | Description |
|-----|------|---------|-------------|
| `themes => path` | Bool/String | `/indexer/themes/` | Set to a path relative to the root directory containing `.css` files. Every `.css` file in the set folder will be treated as a separate theme, as will every sub-directory holding an `index.css` or a `.css` file of the same name.
| `themes => default` | Bool/String | `false` | Default theme for new clients to use. Takes a filename **without** the `.css` extension.
| `css => additional` | Array/String | `false` | Adds any additional CSS to the page. Can either be a pure CSS `string` or an `array` where the key is the selector and where the child keys and values are properties and values respectively.
| `compact` | Bool | `false` | Makes the page use a more compact and centered style.

## Filter
Key: **`filter`**

This option can be used if you want to filter the files or directories using `regular expressions`.

All filenames and directory names **matching** the `regex` will be shown. Please note that directory names will always end in a slash (`/`), this is done to make them easier to differentiate from files.

For example, setting `file` to `/^.{1,10}\.(jpg|png)$/` will only include `.jpg` and `.png` files with a filename between `1 - 10` characters in length when reading the directory files.

#### Another is example is when you want to hide files with invalid characters or specific filenames:

 `'/^(?!README\.md$|secret\.pwd$).*$/'`<br/>
This example will hide any `README.md` and `secret.pwd` files from the directory.

 `'/^[^\#\?]*$/'`<br/>
Will hide any filenames that contains `#` or `?`.

If you want to apply multiple filters easily, then you can also pass them as an array:
```php
'filter' => array(
    'file' => array(
        '/^(?!README\.md$|secret\.pwd$).*$/',
        '/^[^\#\?]*$/'
    ),
    'directory' => false
)
```
This will use both the filters above, and any filename that matches either of those will be hidden.

Setting the value to `false` will disable the filter.

| Child key | Type | Default | Description |
|-----|------|---------|-------------|
| `file` | Bool/String/Array | `false` | A `regexp` filter for what files should be included.
| `directory` | Bool/String/Array | `false` | A `regexp` filter for what directories should be included.

## Exclude
Key: **`exclude`**

This option will exclude certain extensions from showing up in any directory.

An example that'll exclude any `jpg` or `jpeg` files:
```php
<?php
    return array(
        'exclude' => ["jpg", "jpeg"]
    );
?>
```

## Directory Sizes
Key: **`directory_sizes`**

Shows the sizes of directories.

Leaving this off is recommended as calculating the directory sizes can be a bit intensive, especially with the recursive option.

| Child key | Type | Default | Description |
|-----|------|---------|-------------|
| `enabled` | Bool | `false` | Whether directory sizes should be calculated or not.
| `recursive` | Bool | `false` | Recursively scans the directories when calculating the size.

## Footer
Key: **`footer`**

Shows a footer with some general information at the bottom of the page.

| Child key | Type | Default | Description |
|-----|------|---------|-------------|
| `enabled` | Bool | `true` | Whether the footer should be displayed or not.
| `show_server_name` | Bool | `true` | Shows the `server_name` of the current server in the footer.

## Other
| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `processor` | Bool | `false` | Allows you to handle and modify data by passing functions to the indexer. See [PROCESSOR](processor.md) for more information.
| `single_page` | Bool | `false` | Enables navigation between folders without forcing a page reload (experimental).
| `encode_all` | Bool | `false` | Should `?` and `#` characters be encoded when processing URLs and filenames.
| `allow_direct_access` | Bool | `false` | Whether direct access to the `indexer.php` should be allowed or not.
| `path_checking` | String | `strict` | Use `weak` if you need to support symbolic link directories. `strict` will use [realpath](https://www.php.net/manual/en/function.realpath.php) when verifiying the location of the current directory, whereas `weak` will use a similar string-based approach which doesn't resolve symbolic links.
| `performance` | Bool | `false` | Enables [performance mode](performance.md). `true` will enable it for all folders, while setting it to a number, will enable it on directories above or equal to that respective amount of files.
| `footer` | Bool | `true` | Setting this to `true` or `false` will enable or disable the path, site and generation time in the footer respectively.
| `credits` | Bool | `true` | When set to true, it will display a simple link to the git repository in the footer along with the version number. I would appreciate it if you keep this enabled, but i also understand that it is not always desirable, so the option to hide it is there.
| `trust_prepend_header` | Bool | `false` | Whether the `X-Indexer-Prepend-Path` request header is honoured. See [Path Prepending](#path-prepending). Only enable this when a reverse proxy sets the header and strips any copy sent by the client.
| `debug` | Bool | `false` | Enables PHP debugging and `console.log()` info messages. Keep this disabled in production: it sends exception traces, which contain absolute filesystem paths, to the browser.

# Advanced
## Server <!-- {docsify-ignore} -->

Some server variables can be passed to the script via your web server in order to modify some of the more advanced features of the script.

### Base Path
| Key | Type | Description |
|-----|------|-------------|
| `INDEXER_BASE_PATH` | String | Overrides the default base directory of the script.

This option can be used if you are dealing with a dynamic `root` path or if you want to place the script outside of the `root` directory, for example.

This does **not** work with themes out of the box.

Example usage with Nginx:
```
    location ~ ^/.+\.php(/|$) {
        ...
        fastcgi_param INDEXER_BASE_PATH     /servePoint;
        ...
    }
```

### Path Prepending
| Key | Type | Description |
|-----|------|-------------|
| `INDEXER_PREPEND_PATH` | String | Adds a path to the beginning of every parsed path and file in the script.

This option can be used if you for example are serving the script through a reverse proxy to a path that is not the actual web root.

This can also be set as a header (`X-Indexer-Prepend-Path`) in cases where the script is being processed through a proxy. The header is **ignored unless `trust_prepend_header` is enabled** in the configuration.

> **Note:** the header rewrites every link the page emits, so it must only be trusted where a reverse proxy sets it and strips any copy sent by the client. A visitor can send this header directly, so honouring it on a server that is reachable without the proxy hands link control to the visitor. Prefer the `INDEXER_PREPEND_PATH` server variable, which a client cannot reach. Regardless of the source, the value is discarded unless it is path shaped.

#### An example where this can be used:

Assume you are serving the script through a proxied server:
```
location /file/upstream/ {
        proxy_pass http://192.168.1.100:8080/;

        proxy_set_header X-Indexer-Prepend-Path "/file/upstream/";
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
}
```

If `X-Indexer-Prepend-Path` is excluded from this configuration, the script would not know that it is actually supposed to serve the files from `/file/upstream/` instead of `/`, thus the navigation and file links would point to incorrect directories and files.

By setting the prepend value to `/file/upstream/`, every link will have that string prepended to it, so a file like `/image.jpg` would correctly point to `/file/upstream/image.jpg`.

#### Note:

This does not prepend any assets file, so the assets will have to be placed in the root directory or forwarded from a directory or a proxy to `/indexer/` in the web root.
