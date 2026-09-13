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
| `IVFI_MAX_UPLOAD_SIZE` | `100M` | Largest accepted file. The request body limits are set a megabyte above it, since the multipart envelope and the other fields are counted too. Cloudflare's proxy caps request bodies at 100 MB on most plans. |
| `IVFI_BEHIND_PROXY` | `true` | Trust forwarded headers for the client address and HTTPS. |
| `IVFI_CLIENT_IP_HEADER` | `CF-Connecting-IP` | Header carrying the client address. Use `X-Forwarded-For` without Cloudflare. |

Without both a username and a password the index is public and uploads are off.

Anything else can go in a PHP file mounted at `/etc/ivfi/config.php`, returning
an array in the usual config format. It is merged over the generated config.

## The client address comes from a header

`IVFI_BEHIND_PROXY` is on and the address is read from `IVFI_CLIENT_IP_HEADER`,
because behind a proxy `REMOTE_ADDR` is the proxy for every visitor and the
failed-login lockout would then apply to everybody at once, which anyone could
trigger on purpose.

The cost is that the header is only as trustworthy as the path in front of it.
Reach the container any other way than through the proxy that sets it, and a
visitor can send the header themselves, choose their own lockout bucket, and
guess passwords indefinitely. That is fine when the proxy is the only ingress,
which is the point to confirm rather than assume: publish the container to the
proxy alone, not to a host port.

## Direct file URLs are not gated

Authentication covers the listing. Files under `/data` are served by nginx
directly, so anyone holding a file's URL can fetch it without signing in. Put
authentication at the proxy (Cloudflare Access, a Traefik middleware) if the
files themselves are private.
