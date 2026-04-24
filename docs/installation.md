# Installation

## 1. Require The Package

```bash
composer require deadcode/deadcode-laravel
php artisan vendor:publish --tag=oxcribe-config
```

## 2. Install Or Build `deadcore`

Fast path:

```bash
php artisan deadcode:install-binary v0.1.4
```

That downloads the matching release binary, verifies the published checksum, and installs it into the app-local binary path.

If you have a local `deadcore` checkout and want a source-backed install path that does not depend on release assets, point `deadcode-laravel` at the checkout and either prefer or force source builds:

```bash
php artisan deadcode:install-binary v0.1.4 --source-root=/absolute/path/to/deadcore --prefer-source
```

Or set it once in the environment:

```env
DEADCORE_SOURCE_ROOT=/absolute/path/to/deadcore
```

When `DEADCORE_SOURCE_ROOT` is configured, `deadcode:install-binary` will automatically fall back to building from source if the tagged release is missing binary assets or checksums. Source builds use the checked-out local tree; the requested version remains the preferred release tag for remote downloads.

Manual fallback:

`deadcode-laravel` shells out to a local `deadcore` binary. `deadcore` is the analysis engine and can be built directly from the source checkout.

```bash
cargo build --locked --release
cargo test --locked
```

## 3. Point The Package At The Binary

```env
DEADCORE_BINARY=/absolute/path/to/deadcore
DEADCORE_SOURCE_ROOT=/absolute/path/to/deadcore
DEADCORE_WORKING_DIRECTORY=/absolute/path/to/your/laravel/app
DEADCORE_TIMEOUT=120
```

If the binary is already on `PATH`, `DEADCORE_BINARY` can stay as `deadcore`.
`deadcode:install-binary` writes to the app-local install path by default, so you normally do not need an absolute path after running it.

## 4. Run Analysis

```bash
php artisan deadcode:doctor
php artisan deadcode:analyze
php artisan deadcode:report --format=table
```

If you need to point the analyzer at another Laravel app root, pass it explicitly:

```bash
php artisan deadcode:analyze /absolute/path/to/laravel-app
```

`deadcode:analyze` does not support `--write` or `--pretty`. It streams runtime progress and prints the finding count plus generated report path. Use `deadcode:report --write=... --pretty` when you need a persisted or prettified artifact.

## 5. Render Or Persist A Report

```bash
php artisan deadcode:report --format=json --write=storage/app/deadcode-report.json --pretty
php artisan deadcode:report --input=storage/app/deadcode-report.json --format=table
```

If the preflight fails, start with [docs/troubleshooting.md](troubleshooting.md).
