# Container image

The `Dockerfile` at the repository root builds the indexer from source and
serves it with nginx and php-fpm in one container, which is the shape Coolify
and similar platforms expect. The two-container setup in `docker/` remains for
local use.

The browsable directory is `/data`. Mount a persistent volume there: uploads
are written into it, and it is chowned to `www-data` at start if it is not
already.

## Environment

| Variable | Default | Description |
|----------|---------|-------------|
| `IVFI_USERNAME` | | Sign-in name. With a password, enables authentication and uploads. |
| `IVFI_PASSWORD` | | Plaintext password, hashed at container start. |
| `IVFI_PASSWORD_HASH` | | A `password_hash()` value, used instead of `IVFI_PASSWORD`. |
| `IVFI_AUTH_RESTRICT` | | Regex; authenticate only matching paths. Uploads follow it. |
| `IVFI_UPLOAD` | `true` | Whether signed-in users may upload. |
| `IVFI_UPLOAD_EXTENSIONS` | media types | Comma-separated allowlist, e.g. `jpg,png,mp4`. |
| `IVFI_UPLOAD_OVERWRITE` | `false` | Whether an upload may replace an existing file. |
| `IVFI_UPLOAD_RESTRICT` | | Regex; accept uploads only on matching paths. |
| `IVFI_MAX_UPLOAD_SIZE` | `100M` | Per-file limit for both nginx and PHP. Cloudflare's proxy caps request bodies at 100 MB on most plans. |
| `IVFI_BEHIND_PROXY` | `true` | Trust forwarded headers for the client address and HTTPS. |
| `IVFI_CLIENT_IP_HEADER` | `CF-Connecting-IP` | Header carrying the client address. Use `X-Forwarded-For` without Cloudflare. |

Without both a username and a password the index is public and uploads are off.

Anything else can go in a PHP file mounted at `/etc/ivfi/config.php`, returning
an array in the usual config format. It is merged over the generated config.

## Direct file URLs are not gated

Authentication covers the listing. Files under `/data` are served by nginx
directly, so anyone holding a file's URL can fetch it without signing in. Put
authentication at the proxy (Cloudflare Access, a Traefik middleware) if the
files themselves are private.
