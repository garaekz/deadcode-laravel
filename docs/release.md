# Release Checklist

## Before Tagging

- `composer validate --strict`
- `./vendor/bin/pest`
- `cargo test --locked` in `deadcore`
- `cargo build --locked --release` in `deadcore`
- verify `deadcode:install-binary` still matches the current `deadcore` release/build contract
- verify the GitHub Actions matrix passes for Laravel `10`, `11`, `12` and `13`

## Package Metadata

- keep `composer.json` without a hardcoded `version`
- update `CHANGELOG.md`
- make sure docs mention current limitations and supported stacks
- verify `deadcode:install-binary` still works against the tagged `deadcore` release and against a local `DEADCORE_SOURCE_ROOT` checkout

## Binary Contract

`deadcode:install-binary` expects the tagged `deadcore` release to expose:

- platform binaries named `deadcore_<tag>_<os>_<arch>[.exe]`
- a `checksums.txt` file in the same release

If a release is missing those assets, the supported fallback is to configure `DEADCORE_SOURCE_ROOT` and build from source locally.

## Real App Smoke

- install `deadcode/deadcode-laravel` in at least one real Laravel app
- publish config
- run `php artisan deadcode:doctor`
- run `php artisan deadcode:analyze`
- run `php artisan deadcode:report --format=json --write=storage/app/deadcode-report.json --pretty`
- inspect output for runtime progress, finding totals, report path generation, and rendered report contents
- if you do not have an external app yet, rerun the owned-app smoke before tagging and clearly label the release as a preview
