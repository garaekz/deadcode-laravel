# Compatibility And Fixtures

## Supported Compatibility Matrix

- Laravel `10` with Testbench `8`
- Laravel `11` with Testbench `9`
- Laravel `12` with Testbench `10`
- Laravel `13` with Testbench `11`

Package CI is intended to run the same Pest suite across that matrix.

## Runtime/Static Split

- Laravel runtime truth is captured by the supervisor-backed `deadcode:analyze {projectPath?}` flow.
- `deadcode-supervisor` is resolved through `DEADCODE_SUPERVISOR_BINARY` or the app-local `DEADCODE_SUPERVISOR_INSTALL_PATH` / `deadcode.supervisor_install_path`.
- `deadcode:analyze` writes a `deadcode.analysis.v1` payload and prints the generated analysis payload path.
- `deadcode:report --input=...` renders existing analysis payloads only; it does not run runtime capture or static analysis.
- Remediation commands consume the same generated analysis payload.

## Hostile Fixture Apps

The package suite keeps real fixture apps instead of mocking the contract:

- `SpatieLaravelApp`
- `InertiaLaravelApp`
- `AuthErrorLaravelApp`
- `PolicyLaravelApp`

Each fixture is expected to pass both:

- `deadcode:analyze`
- `deadcode:report --input=...`

## Current Non-Goals

- Livewire
- non-Laravel frameworks
- automatic removal outside high-confidence supported change sets
- report rendering without an existing analysis payload
