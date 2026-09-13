<h1 align="center">Building</h1>

<p align="center">Instructions on how to build from source.</p>

<br/>

You can build this script from source using `node` and `npm`.

**Clone repository and install dependencies:**
```bash
git clone https://github.com/sixem/ivfi-php
cd ivfi-php
npm install
```

## Production builds

Build from source, creating minified files:

```bash
npm run build
```

Build a standalone file from source:

```bash
npm run make-standalone
```

This will place the compiled files in a new `build` directory.

## Development builds

Build source mapped, non-production files:

```bash
npm run build-dev
```

## Build Options

You can edit `build.options.json` to enable extra features or change output options:

```json
{
    "extraFeatures": {
        "readmeSupport": false
    },
    "extrasDir": "extras",
    "assetDir": "indexer"
}
```
* `extraFeatures` will enable or disable features when building. For more information see [extras](extras.md).

* `extrasDir` sets the directory where `extras` are located.

* `assetDir` sets the directory where resources (`.js`, `.css` and fonts) will be placed in. This also affects any references in the HTML/CSS.

## Testing

The PHP suite runs against the **built** `indexer.php` rather than the webpack
template, so build before testing:

```bash
npm run build
composer install
vendor/bin/phpunit
```

It covers output encoding against a fixture tree of hostile filenames, the
prepend-path header, error handling, path containment, hostile client cookies,
theme discovery, configuration defaults, and the whole sign-in flow. There are
also golden-file snapshots of whole rendered listings.

When a change to the markup is intended, regenerate the snapshots and read the
diff before committing it:

```bash
UPDATE_GOLDEN=1 vendor/bin/phpunit
```

The authentication tests drive the real sign-in flow against PHP's built-in web
server, so they use the session cookie and form token the server actually
issued rather than reconstructing them. That also covers the response status
codes and headers, which the CLI does not emit. No extra binaries are needed.

The suite itself needs PHP 8.2 or newer, because that is PHPUnit 11's floor.
That is a constraint on the tooling, not on the script, which still runs on
older versions.


## Staged TypeScript work

`strict` is not on yet. The flags were measured individually against the current
source on TypeScript 5.9, so the remaining work is a known quantity rather than
a guess:

| Flag | Errors |
|------|--------|
| `strictBindCallApply` | 0, enabled |
| `noImplicitReturns` | 2 |
| `strictFunctionTypes` | 10 |
| `noImplicitThis` | 104 |
| `noImplicitAny` | 233 |
| `strictNullChecks` | 805 |
| `strict` (all of the above together) | 1122 |

`strictNullChecks` could not be measured before the compiler was upgraded,
because TypeScript 4.8 crashed on this source with an internal error rather
than reporting diagnostics. It is by far the largest item.

A sensible order is `noImplicitReturns`, `strictFunctionTypes`,
`noImplicitThis`, `noImplicitAny`, then `strictNullChecks`, one flag per change
so each one is reviewable.
