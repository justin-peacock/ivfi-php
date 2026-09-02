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
prepend-path header, error handling, path containment, and a real HTTP digest
exchange. There are also golden-file snapshots of whole rendered listings.

When a change to the markup is intended, regenerate the snapshots and read the
diff before committing it:

```bash
UPDATE_GOLDEN=1 vendor/bin/phpunit
```

The authentication tests drive a genuine digest challenge and response against
PHP's built-in web server, so they read the nonce the server actually issued
rather than reconstructing it. That also covers the response status codes, which
the CLI does not emit. No extra binaries are needed.


## Staged TypeScript work

`strict` is not on yet. The flags were measured individually against the current
source so the remaining work is a known quantity rather than a guess:

| Flag | Errors |
|------|--------|
| `strictBindCallApply` | 0, enabled |
| `noImplicitReturns` | 2 |
| `strictFunctionTypes` | 10 |
| `noImplicitThis` | 104 |
| `noImplicitAny` | 233 |
| `strictNullChecks` | not measurable yet |

`strictNullChecks`, and therefore `strict` as a whole, cannot be assessed on
TypeScript 4.8: the compiler crashes with an internal error rather than
reporting diagnostics. **TypeScript has to be upgraded before strict mode can
even be scoped**, so that is the first step, not the last.

A sensible order after that is `noImplicitReturns`, `strictFunctionTypes`,
`noImplicitThis`, `noImplicitAny`, then `strictNullChecks`, one flag per change
so each one is reviewable.

