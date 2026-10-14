<p align="center">
  <a href="https://sendkuy.com/?utm_source=readme&utm_medium=sdk&utm_campaign=sendkuy-php">
    <img src="https://raw.githubusercontent.com/sendkuy/sendkuy-php/main/art/banner.png" alt="SendKuy PHP SDK" width="100%">
  </a>
</p>

# SendKuy PHP SDK

Send WhatsApp, SMS and email from PHP with one API key, and retry without ever sending twice.

[![Latest version](https://img.shields.io/packagist/v/sendkuy/sdk)](https://packagist.org/packages/sendkuy/sdk)
[![Downloads](https://img.shields.io/packagist/dt/sendkuy/sdk)](https://packagist.org/packages/sendkuy/sdk)
[![CI](https://github.com/sendkuy/sendkuy-php/actions/workflows/ci.yml/badge.svg)](https://github.com/sendkuy/sendkuy-php/actions/workflows/ci.yml)
[![PHPStan level max](https://img.shields.io/badge/PHPStan-level%20max-brightgreen)](phpstan.neon)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue)](LICENSE)

[Baca dalam Bahasa Indonesia](README.id.md)

## Install

```bash
composer require sendkuy/sdk
```

PHP 8.2 or newer. Building on Laravel? Install [`sendkuy/laravel`](https://github.com/sendkuy/sendkuy-laravel) instead: it adds a facade, a notification channel and a signed webhook route on top of this package.

## Quickstart

```php
use SendKuy\SendKuy;

$sendkuy = new SendKuy(getenv('SENDKUY_API_KEY'));

$messages = $sendkuy->messages()->send([
    'channel' => 'whatsapp',
    'to' => ['+6281234567890'],
    'body' => 'Your order is on its way.',
]);

echo $messages[0]->id; // msg_...
```

Create a key in the [SendKuy dashboard](https://app.sendkuy.com/?utm_source=readme&utm_medium=sdk&utm_campaign=sendkuy-php) under **Developers → Keys**. Numbers go in E.164 form, with the leading `+`. You get one message back per recipient.

## Every endpoint has a method

Field names are the API's own, so every example in the [API reference](https://docs.sendkuy.com/?utm_source=readme&utm_medium=sdk&utm_campaign=sendkuy-php) maps one to one onto this SDK.

| Group | Methods |
| --- | --- |
| `account()` | `get`, `usage` |
| `messages()` | `list`, `send`, `sendBatch`, `get`, `cancel`, `resend` |
| `numbers()` | `check` |
| `verifications()` | `create`, `get`, `check` |
| `jobs()` | `get` |
| `contacts()` | `list`, `create`, `bulkImport`, `lookup`, `get`, `update`, `delete`, `listAttributes` |
| `groups()` | `list`, `create`, `get`, `update`, `delete`, `addContacts`, `removeContacts` |
| `templates()` | `list`, `create`, `get`, `update`, `delete` |
| `gateways()` | `list`, `get` |
| `webhooks()` | `list`, `create`, `get`, `update`, `delete`, `listDeliveries` |
| `campaigns()` | `list`, `create`, `get`, `update`, `delete`, `listDispatches`, `start`, `pause`, `resume`, `cancel` |
| `conversations()` | `list`, `get`, `listMessages`, `markRead`, `reply` |
| `workflows()` | `list`, `get`, `listExecutions`, `trigger` |
| `executions()` | `get` |

Objects come back with their fields as properties (`$message->status`) and as array keys (`$message['status']`), and your editor autocompletes them. An endpoint newer than your SDK version is still one call away:

```php
$response = $sendkuy->request('GET', '/account');
```

## Retries and idempotency

A timeout tells you nothing about whether a message went out. That is why every write this SDK sends carries an `Idempotency-Key`, and every retry of that call reuses it: if the first attempt did go through, SendKuy answers the retry with the first result instead of sending again.

The SDK retries connection errors, timeouts, `429` (waiting for `Retry-After` when it is a minute or less), `5xx` and `409 idempotency_request_in_progress`, up to twice, with exponential backoff.

When your own code retries, for example a queued job, pass a key derived from something stable in your system:

```php
$sendkuy->messages()->send($params, ['idempotency_key' => 'order-1042-shipped']);
```

| Option | Default | |
| --- | --- | --- |
| `base_url` | `https://app.sendkuy.com/api/v1` | |
| `timeout` | `30` | seconds; also per request: `['timeout' => 10]` |
| `max_retries` | `2` | |
| `http_client` | Guzzle | any PSR-18 client |

## Pagination

```php
foreach ($sendkuy->contacts()->list(['limit' => 100])->autoPaginate() as $contact) {
    echo $contact->id;
}

$page = $sendkuy->contacts()->list();
$page->data;       // this page
$page->hasMore;
$page->nextPage(); // null on the last page
```

## Errors

Every failure is a `SendKuy\Exceptions\SendKuyException`. Branch on the stable code, never on the message:

```php
use SendKuy\Exceptions\InvalidRequestException;
use SendKuy\Exceptions\RateLimitException;
use SendKuy\Exceptions\SendKuyException;

try {
    $sendkuy->messages()->send($params);
} catch (InvalidRequestException $e) {
    $e->getErrorCode(); // validation_failed
    $e->getParam();     // to
} catch (RateLimitException $e) {
    $e->getRetryAfter(); // seconds
} catch (SendKuyException $e) {
    $e->getRequestId(); // quote it when you contact support
}
```

| Exception | When |
| --- | --- |
| `AuthenticationException` | the key is missing, wrong, revoked or expired |
| `AuthorizationException` | the key lacks a scope, or the plan lacks the channel |
| `InvalidRequestException` | validation, not found, idempotency conflicts |
| `RateLimitException` | too many requests |
| `ApiException` | SendKuy failed on its side |
| `ConnectionException` | SendKuy could not be reached |

## Webhooks

SendKuy signs every webhook. Verify it against the raw body, before any JSON parsing:

```php
use SendKuy\Exceptions\SignatureVerificationException;
use SendKuy\Webhook;

try {
    $event = Webhook::verify(
        file_get_contents('php://input'),
        $_SERVER['HTTP_X_SENDKUY_SIGNATURE'] ?? '',
        getenv('SENDKUY_WEBHOOK_SECRET'),
    );
} catch (SignatureVerificationException $e) {
    http_response_code(403);
    exit;
}

$deliveryId = $_SERVER['HTTP_X_SENDKUY_EVENT_ID'] ?? ''; // the same on every retry: use it to skip duplicates

if ($event['event'] === 'message.received') {
    // ...
}

http_response_code(200);
```

Use the endpoint secret in full, `whsec_` included, and answer within 10 seconds. Slow work belongs in a queue.

## Self-hosted SendKuy

```php
$sendkuy = new SendKuy($key, ['base_url' => 'https://sendkuy.example.com/api/v1']);
```

## Testing your code

Pass any PSR-18 client and the SDK sends through it:

```php
$sendkuy = new SendKuy('sk_test_123', ['http_client' => $fakeClient]);
```

A client you pass in owns its own timeouts.

## Links

- [Documentation](https://docs.sendkuy.com/?utm_source=readme&utm_medium=sdk&utm_campaign=sendkuy-php)
- [SendKuy](https://sendkuy.com/?utm_source=readme&utm_medium=sdk&utm_campaign=sendkuy-php)
- [Support](https://sendkuy.com/contact?utm_source=readme&utm_medium=sdk&utm_campaign=sendkuy-php)
- [Changelog](CHANGELOG.md)

## License

MIT. See [LICENSE](LICENSE).
