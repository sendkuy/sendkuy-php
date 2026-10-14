<p align="center">
  <img src="https://raw.githubusercontent.com/sendkuy/sendkuy-php/main/art/banner.png" alt="SendKuy PHP SDK" width="100%">
</p>

# SendKuy PHP SDK

Kirim WhatsApp, SMS, dan email dari PHP dengan satu API key. Retry-nya aman, tidak pernah mengirim dobel.

[Read in English](README.md)

## Instalasi

```bash
composer require sendkuy/sdk
```

Butuh PHP 8.2 atau lebih baru. Pakai Laravel? Pasang [`sendkuy/laravel`](https://github.com/sendkuy/sendkuy-laravel) saja: paket itu menambahkan facade, notification channel, dan rute webhook bertanda tangan di atas paket ini.

## Mulai cepat

```php
use SendKuy\SendKuy;

$sendkuy = new SendKuy(getenv('SENDKUY_API_KEY'));

$messages = $sendkuy->messages()->send([
    'channel' => 'whatsapp',
    'to' => ['+6281234567890'],
    'body' => 'Pesanan kamu sedang dikirim.',
]);

echo $messages[0]->id; // msg_...
```

Buat API key di [dashboard SendKuy](https://app.sendkuy.com/?utm_source=readme&utm_medium=sdk&utm_campaign=sendkuy-php), menu **Developers → Keys**. Nomor ditulis dalam format E.164, diawali `+`. Hasilnya satu pesan per penerima.

## Retry dan idempotensi

Setiap request tulis otomatis membawa `Idempotency-Key`, dan setiap retry memakai key yang sama. Kalau percobaan pertama ternyata sudah sampai, SendKuy menjawab retry dengan hasil pertama, bukan mengirim lagi. SDK mengulang kegagalan koneksi, timeout, `429`, `5xx`, dan `409 idempotency_request_in_progress`, maksimal dua kali.

Kalau kode kamu sendiri yang mengulang (misalnya job di queue), beri key dari data yang stabil:

```php
$sendkuy->messages()->send($params, ['idempotency_key' => 'order-1042-shipped']);
```

## Paginasi

```php
foreach ($sendkuy->contacts()->list(['limit' => 100])->autoPaginate() as $contact) {
    echo $contact->id;
}
```

## Error

Semua kegagalan adalah `SendKuyException`. Bercabanglah berdasarkan `getErrorCode()`, bukan pesan:

```php
try {
    $sendkuy->messages()->send($params);
} catch (\SendKuy\Exceptions\InvalidRequestException $e) {
    $e->getErrorCode(); // validation_failed
    $e->getParam();     // to
}
```

## Webhook

```php
$event = \SendKuy\Webhook::verify(
    file_get_contents('php://input'),
    $_SERVER['HTTP_X_SENDKUY_SIGNATURE'] ?? '',
    getenv('SENDKUY_WEBHOOK_SECRET'),
);
```

Pakai secret endpoint secara utuh, termasuk `whsec_`, dan body mentah sebelum di-parse. Jawab dalam 10 detik.

Dokumentasi lengkap ada di [README bahasa Inggris](README.md) dan [docs.sendkuy.com](https://docs.sendkuy.com/?utm_source=readme&utm_medium=sdk&utm_campaign=sendkuy-php).

## Lisensi

MIT.
