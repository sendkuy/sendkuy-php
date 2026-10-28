# Changelog

All notable changes to this project are documented here. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html). Version 1.x speaks SendKuy API v1.

## [1.1.0] - 2026-10-28

### Added

- The fields of a `message.received` event on `ConversationMessage`: `channel`, `from`, `gateway_id`, `provider_message_id`, `received_at`, `subject` and `media`.
- Recurring campaign fields on `Campaign` and `CampaignDispatch`.

### Changed

- The bundled API contract follows SendKuy API v1 as of this release. Behaviour of the API that this SDK now describes:
  - `messages()->send()` with more than one recipient (a group counts as its members) and `messages()->sendBatch()` answer at once with `queued`; the messages are sent in the background, paced per number. One recipient is still sent while you wait.
  - `schedule_at` must carry a time zone offset; a `DateTimeInterface` always does.
  - A retry with the same idempotency key after a server error returns the message that already went out instead of sending it again.
  - `message.received` also fires for SMS and WhatsApp Cloud API, and its `id` and `conversation_id` are `null` when the account forwards incoming messages to its webhook only.

## [1.0.0] - 2026-10-14

### Added

- A method for each of the 61 operations of the SendKuy API v1.
- Automatic idempotency keys, reused across retries of the same call.
- Retries for connection errors, timeouts, 429, 5xx and in-progress idempotency conflicts.
- Typed exceptions carrying the error code, parameter, documentation link and request id.
- Cursor pagination with `autoPaginate()`.
- `Webhook::verify()` for signed webhook deliveries.
