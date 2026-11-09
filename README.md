# Laravel DukPay

Package Laravel untuk **DukPay Merchant API v3** dengan **Security Authentication V2**:

- Request dienkripsi hybrid (**AES-256-GCM** + **RSA-OAEP SHA-256**) dan ditandatangani **SHA256withRSA**.
- Response diverifikasi (`X-DUKPAY-RESP-SIGN`) lalu didekripsi otomatis.
- Route callback siap pakai: verifikasi signature, dekripsi, dispatch event, balas `{"code":0}`.
- Enkripsi input kartu `MERCHANT_AES:` untuk alur Bank Card Direct.

Mendukung Laravel 10 – 13, PHP 8.1+.

## Instalasi

```bash
composer require rrq/dukpay
php artisan vendor:publish --tag=dukpay-config
```

## Konfigurasi `.env`

```dotenv
DUKPAY_BASE_URL=https://pre-payment.dukpay.com     # production: minta ke DukPay
DUKPAY_SECRET_ID=xxxxxxxx
DUKPAY_SECRET_KEY=xxxxxxxx                         # hanya untuk Bank Card Direct (MERCHANT_AES)
DUKPAY_MERCHANT_PRIVATE_KEY=MIIEvQIBADANBg...      # Base64 PKCS#8 DER satu baris, PEM, atau path file
DUKPAY_PUBLIC_KEY=MIIBIjANBgkqhkiG9w0B...           # Base64 X.509 DER satu baris, PEM, atau path file
DUKPAY_PRODUCT_ID=                                 # opsional (X-PRODUCT-ID)
DUKPAY_CALLBACK_URL=https://shop.example.com/dukpay/callback   # default callbackUrl
DUKPAY_REDIRECT_URL=https://shop.example.com/payment/finish     # default redirectUrl
```

### Membuat key merchant

```bash
openssl genpkey -algorithm RSA -pkeyopt rsa_keygen_bits:2048 -out merchant_private.pem
# private key -> DUKPAY_MERCHANT_PRIVATE_KEY
openssl pkcs8 -topk8 -nocrypt -in merchant_private.pem -outform DER | base64 -w 0
# public key -> kirim ke DukPay untuk aktivasi V2
openssl rsa -pubout -in merchant_private.pem -outform DER | base64 -w 0
```

> Key test dan production berbeda. Jangan commit private key ke repository.

## Pemakaian

Semua method mengembalikan `Rrq\Dukpay\DukPayResponse` yang `data`-nya sudah didekripsi.
Jika `code != 0`, dilempar `DukPayApiException` (bisa dimatikan via `DUKPAY_THROW_ON_ERROR=false`
atau `['throw' => false]` pada `DukPay::call()`).

```php
use Rrq\Dukpay\Facades\DukPay;
use Rrq\Dukpay\Resources\Resource;

// Payin Indonesia (QRIS / DANA / OVO / SHOPEEPAY / LINKAJA / BANK_TRANSFER)
$res = DukPay::payins()->create([
    'businessNo'    => 'INV-2026-0001',
    'paymentMethod' => 'QRIS',
    'country'       => 'IDN',
    'currency'      => 'IDR',
    'orderAmount'   => '50000',
    'properties'    => ['email' => 'buyer@example.com', 'language' => 'ID'],
]);

$res->data('orderNo');                    // T2026...
$res->data('channelAttrs.checkoutUrl');   // redirect user ke sini
$res['channelAttrs.qrCode'];              // string QRIS
```

`requestTime`, `callbackUrl`, dan `redirectUrl` diisi otomatis jika tidak dikirim (dari config).

### Daftar method

| Method | X-DUKPAY-API | Dokumen |
| --- | --- | --- |
| `payins()->create($payload)` | `orderCreateV2` | 4.1.3 – 4.1.35 |
| `payins()->hostedCheckout($payload)` | `advanceOrder` | 4.1.2 |
| `payins()->query($businessNo)` | `orderQueryV2` | 4.3 |
| `payins()->createCardOrder($payload)` | `challengeOrder` | 4.1.36.1 |
| `payins()->advanceChallenge($token, $index, $input)` | `challengeOrder` | 4.1.36.2 |
| `payouts()->create($payload)` | `payoutCreate` | 4.2 |
| `payouts()->query($businessNo, $orderNo)` | `payoutQuery` | 4.5 |
| `refunds()->create($payload)` | `refund` | 6.1 |
| `refunds()->query($businessNo, $orderNo)` | `refundQuery` | 6.2 |
| `accounts()->create($payload)` / `createV2($payload)` | `accountCreate` / `accountCreateV2` | 5.1 |
| `accounts()->query($accountId, $businessNo)` | `accountQuery` | 5.2 |
| `accounts()->charge($payload)` | `credentialWithhold` | 5.4 |
| `accounts()->chargeQuery($businessNo, $orderNo)` | `credentialWithholdQuery` | 5.5 |
| `accounts()->revokeCredential($accountId)` | `credentialManagement` | 5.7 |
| `subscriptions()->create($payload)` | `subscriptionCreate` | 5.8.1 |
| `subscriptions()->cancel($id)` | `subscriptionCancel` | 5.8.2 |
| `subscriptions()->query($id)` | `subscriptionQuery` | 5.8.3 |
| `subscriptions()->refund($payload)` | `subscriptionRefund` | 5.8.5 |
| `subscriptions()->switchPlan($payload)` | `switchSubscriptionPlans` | 5.8.6 |
| `finance()->balance($currency = null)` | `accountBalance` | 8.1 |
| `finance()->currencyExchange($from, $to, $amount)` | `currencyExchange` | 8.2 |
| `finance()->reconciliation($payload)` | `financial` | 8.3 |
| `finance()->profitSharing($orderNo)` | `profitSharingQuery` | 8.4.1 |
| `DukPay::call($api, $payload)` | apa saja | — |

### Payout

```php
DukPay::payouts()->create([
    'businessNo'   => 'WD-0001',
    'country'      => 'KOR',
    'payoutMethod' => 'BANK_TRANSFER',
    'currency'     => 'KRW',
    'amount'       => Resource::amount(10000),   // "10000.00"
    'customerInfo' => ['accountNo' => '1234567890', 'bankCode' => 'KOOKMIN'],
]);
```

Code `2014` = `businessNo` payout sudah ada:

```php
use Rrq\Dukpay\Exceptions\DukPayApiException;

try {
    DukPay::payouts()->create($payload);
} catch (DukPayApiException $e) {
    if ($e->getCode() !== 2014) {
        throw $e;
    }
    // 2014: sudah pernah dibuat -> cek statusnya
    $existing = DukPay::payouts()->query('WD-0001');
}
```

### Bank Card Direct (challenge flow)

```php
// 1. Buat order (tanpa data kartu)
$res = DukPay::payins()->createCardOrder([
    'businessNo' => 'CARD-0001', 'country' => 'RUS', 'currency' => 'RUB',
    'orderAmount' => '100.00', 'properties' => ['clientIp' => $request->ip()],
]);
$token = $res->data('token');
$next  = $res->data('action.nextIndex');

// 2. Kirim data kartu – dienkripsi MERCHANT_AES otomatis di server
$res = DukPay::payins()->advanceChallenge($token, $next, [
    'cardNo' => '4111111111111111', 'expiryMonth' => '12', 'expiryYear' => '2028',
    'cvv' => '123', 'cardHolderName' => 'CARD HOLDER',
]);

// action.type INPUT       -> tampilkan action.inputFields (mis. otp), lalu advanceChallenge() lagi
//                            dengan currentIndex = action.nextIndex
// action.type REDIRECT    -> return redirect()->away($res->data('action.redirectUrl'));
// action.type SUBMIT_FORM -> submit action.formData.payload ke action.formData.targetUrl dari browser user
// action.nextIndex 200    -> challenge selesai; cek status final via payins()->query() / callback
// action.nextIndex -2/-1  -> token expired / gagal; mulai ulang dengan order baru
```

Jangan pernah log/menyimpan nomor kartu, CVV, OTP, PIN.

## Callback (notifikasi asinkron)

Package mendaftarkan route `POST /dukpay/callback` (ubah via `DUKPAY_WEBHOOK_PATH`, matikan via
`DUKPAY_WEBHOOK_ENABLED=false`). Route ini tidak memakai middleware `web`, jadi tidak perlu
pengecualian CSRF. Daftarkan URL ini ke DukPay atau kirim lewat `callbackUrl`.

Setiap callback valid memicu event `DukPayCallbackReceived`:

```php
use Illuminate\Support\Facades\Event;
use Rrq\Dukpay\Events\DukPayCallbackReceived;
use Rrq\Dukpay\Support\CallbackType;

Event::listen(function (DukPayCallbackReceived $event) {
    $cb = $event->callback;

    match ($cb->type) {
        CallbackType::PAYMENT_ORDER => Order::where('number', $cb->businessNo())
            ->where('status', 'pending')                       // idempoten
            ->update(['status' => $cb->isSuccess() ? 'paid' : 'failed']),
        CallbackType::PAYOUT_ORDER => Withdrawal::where('number', $cb->businessNo())
            ->where('status', 'processing')
            ->update(['status' => $cb->isSuccess() ? 'done' : 'failed']),
        default => null,
    };
});
```

- Signature salah → HTTP 401, event tidak dipicu.
- Listener melempar exception → HTTP 500, sehingga DukPay mengirim ulang. Buat listener **idempoten**.
- Untuk menangani callback di controller sendiri: `DukPay::parseCallback($request)` mengembalikan
  `DukPayCallback` yang sudah diverifikasi & didekripsi; balas `response()->json(['code' => 0])`.

Nilai `callback-type`: `payment_order`, `payout_order`, `refund_order`, `subscription_order`,
`withhold_order`, `authorization_account`, `chargeback_order`, `payment_fee`, `payout_fee`,
`refund_fee`, `authorize_fee`.

## Penting

- Create order sukses **bukan** berarti user sudah bayar. Status final dari callback; jika callback
  tidak datang / timeout, lakukan `query`.
- Redirect URL hanya untuk tampilan; jangan dipakai sebagai bukti pembayaran.
- Kode error umum: `1000` param error, `1001` signature error, `1002` request expired,
  `1003` system error, `1004` auth failed, `1005` no permission, `1006` api not found,
  `1010` channel service error, `1011` config or param error, `2001` no available account.

## Catatan implementasi

- RSA-OAEP memakai `phpseclib/phpseclib` v3 karena `ext-openssl` PHP hanya mendukung OAEP SHA-1,
  sedangkan DukPay memakai OAEP SHA-256 / MGF1 SHA-256.
- Implementasi crypto sudah diuji bolak-balik terhadap contoh kode Node.js resmi di dokumen (bagian 3.10).
- Bagian 4.2.1 (*Query Available payout Balance*) di dokumen menulis `X-DUKPAY-API: payoutCreate`,
  yang kemungkinan salah ketik, jadi belum dibuatkan method khusus. Setelah nama API dikonfirmasi ke
  DukPay, panggil dengan `DukPay::call('<nama-api>', ['country' => ..., 'payoutMethod' => ..., 'currency' => ...])`.
  Untuk saldo umum gunakan `finance()->balance()`.

## Testing

```bash
composer install
vendor/bin/phpunit
```
