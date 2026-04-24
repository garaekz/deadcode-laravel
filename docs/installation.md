# Installation

## 1. Require The Package

```bash
composer require deadcode/deadcode-laravel
php artisan vendor:publish --tag=oxcribe-config
```

## 2. Install Or Build The Analysis Engine

Fast path:

```bash
php artisan deadcode:install-binary v0.1.4
```

That downloads the matching `deadcore` release binary, verifies the published checksum, and installs it into the app-local binary path used by the analysis stack.

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

`deadcore` is the static analysis engine and can be built directly from the source checkout.

```bash
cargo build --locked --release
cargo test --locked
```

## 3. Point The Package At The Binaries

```env
DEADCODE_SUPERVISOR_BINARY=/absolute/path/to/deadcode-supervisor
DEADCODE_SUPERVISOR_TIMEOUT=300
DEADCORE_BINARY=/absolute/path/to/deadcore
DEADCORE_SOURCE_ROOT=/absolute/path/to/deadcore
DEADCORE_WORKING_DIRECTORY=/absolute/path/to/your/laravel/app
DEADCORE_TIMEOUT=120
```

`deadcode:analyze` uses `DEADCODE_SUPERVISOR_BINARY`; the supervisor owns the runtime task execution path.

If the binary is already on `PATH`, `DEADCORE_BINARY` can stay as `deadcore`.
`deadcode:install-binary` writes to the app-local install path by default, so you normally do not need an absolute path after running it.

## 4. Run Analysis

`deadcode:analyze {projectPath?}` is supervisor-backed. It starts `deadcode-supervisor`, sends an analysis task for the current Laravel app or the optional project path, streams progress frames, and prints the finding count plus generated `deadcode.analysis.v1` payload path.

Configure the supervisor executable when the default relative path is not valid:

```env
DEADCODE_SUPERVISOR_BINARY=/absolute/path/to/deadcode-supervisor
DEADCODE_SUPERVISOR_TIMEOUT=300
```

```bash
php artisan deadcode:doctor
php artisan deadcode:analyze
```

If the supervisor bootstraps a worker for another Laravel app root, pass that same root explicitly:

```bash
php artisan deadcode:analyze /absolute/path/to/laravel-app
```

The PHP worker rejects mismatches between the bootstrapped Laravel app and the requested project path. That keeps the runtime snapshot, manifest root, deadcore working directory, and output path aligned.

`deadcode:analyze` does not support `--write` or `--pretty`. Use the report path printed by the command as the input for report rendering, remediation, or rollback workflows.

## 5. Render Or Persist A Report

`deadcode:report` only renders an existing `deadcode.analysis.v1` payload. Run `deadcode:analyze` first, then pass the generated path with `--input`.

```bash
php artisan deadcode:report --input=storage/app/deadcode/analysis.json --format=json --write=storage/app/deadcode-report.json --pretty
php artisan deadcode:report --input=storage/app/deadcode/analysis.json --format=table
```

If the preflight fails, start with [docs/troubleshooting.md](troubleshooting.md).
