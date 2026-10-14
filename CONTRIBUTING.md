# Contributing

Thank you for helping improve the SendKuy PHP SDK.

## Setup

```bash
composer install
composer test      # PHPUnit
composer analyse   # PHPStan, level max
composer format    # Pint
```

## How the SDK stays in sync with the API

`spec/openapi.json` is a copy of the SendKuy API contract, and `spec/operations.json` names the method for each operation. Both are synced by the SendKuy team, so please do not edit them by hand. `tests/ContractTest.php` calls every operation in the spec and fails when a method is missing or points at the wrong route.

`src/Objects` is generated from the spec. After a spec update, run `composer generate`.

## Pull requests

- Keep each pull request to one change, with a test when behaviour changes.
- Use [Conventional Commits](https://www.conventionalcommits.org/) in English, for example `fix(transport): honour Retry-After of zero`.
- Make sure `composer test`, `composer analyse` and `vendor/bin/pint --test` pass.
