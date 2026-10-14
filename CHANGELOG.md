# Changelog

All notable changes to this project are documented here. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html). Version 1.x speaks SendKuy API v1.

## [Unreleased]

### Added

- A method for each of the 61 operations of the SendKuy API v1.
- Automatic idempotency keys, reused across retries of the same call.
- Retries for connection errors, timeouts, 429, 5xx and in-progress idempotency conflicts.
- Typed exceptions carrying the error code, parameter, documentation link and request id.
- Cursor pagination with `autoPaginate()`.
- `Webhook::verify()` for signed webhook deliveries.
