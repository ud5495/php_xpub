# Laravel Integration

This guide shows a production-style integration: dependency injection, Form Request validation,
domain exception handling, an API route, and a feature test.

## Installation

```bash
composer require bedud5495/php_xpub
```

The package is auto-discovered through `extra.laravel`. No configuration file is required.

If your application disables package discovery, register the provider manually.

Laravel 11+ (`bootstrap/app.php`):

```php
->withProviders([
    \Bedud5495\XPub\Laravel\XPubServiceProvider::class,
])
```

Laravel 10 (`config/app.php`):

```php
'providers' => [
    // ...
    Bedud5495\XPub\Laravel\XPubServiceProvider::class,
],

'aliases' => [
    // ...
    'XPub' => Bedud5495\XPub\Laravel\Facades\XPub::class,
],
```

The provider binds `XPubService` as a singleton and exposes the `XPub` facade.

## Form Request

Validation keeps malformed paths and unknown coin/scheme values out of the domain layer.

```php
<?php

namespace App\Http\Requests;

use Bedud5495\XPub\Coin;
use Bedud5495\XPub\Scheme;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class DeriveAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'extended_key' => ['required', 'string', 'max:255'],
            'scheme' => ['nullable', Rule::enum(Scheme::class)],
            'coin' => ['required', Rule::enum(Coin::class)],
            'path' => ['required', 'array', 'min:1', 'max:32'],
            'path.*' => ['integer', 'min:0', 'max:2147483647'],
        ];
    }
}
```

`Rule::enum(...)` validates the backed values:

- `coin`: `btc`, `eth`, `tron`, `ltc`, `doge`, `bch`, `dash`
- `scheme`: `bip-44`, `bip-49`, `bip-84`

## Controller

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\DeriveAddressRequest;
use Bedud5495\XPub\Coin;
use Bedud5495\XPub\Exception\XPubExceptionInterface;
use Bedud5495\XPub\Scheme;
use Bedud5495\XPub\XPubService;
use Illuminate\Http\JsonResponse;

final class XPubController extends Controller
{
    public function __construct(private readonly XPubService $xpub)
    {
    }

    public function derive(DeriveAddressRequest $request): JsonResponse
    {
        $data = $request->validated();
        $path = array_map(static fn (mixed $index): int => (int) $index, $data['path']);

        try {
            $key = $this->xpub->fromString(
                $data['extended_key'],
                isset($data['scheme']) ? Scheme::from($data['scheme']) : null,
            );

            $child = $this->xpub->derive($key, $path);

            return response()->json([
                'address' => $this->xpub->toAddress($child, Coin::from($data['coin'])),
                'extended_key' => $this->xpub->toString($child),
            ]);
        } catch (XPubExceptionInterface $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }
}
```

All library exceptions implement `Bedud5495\XPub\Exception\XPubExceptionInterface`, so one
`catch` handles invalid keys, invalid paths, unsupported schemes, and failed derivations.

## Route

`routes/api.php`:

```php
use App\Http\Controllers\Api\XPubController;
use Illuminate\Support\Facades\Route;

Route::post('/xpub/derive', [XPubController::class, 'derive'])
    ->middleware('auth');
```

## Facade

Use the facade in Artisan commands, jobs, or tinker sessions:

```php
use Bedud5495\XPub\Coin;
use Bedud5495\XPub\Laravel\Facades\XPub;

$key = XPub::fromString('xpub68Gmy5EdvgibQVfPdqkBBCHxA5htiqg55crXYuXoQRKfDBFA1WEjWgP6LHhwBZeNK1VTsfTFUHCdrfp1bgwQ9xv5ski8PX9rL2dZXvgGDnw');
$child = XPub::derive($key, [1]);

$address = XPub::toAddress($child, Coin::BTC); // 1JQheacLPdM5ySCkrZkV66G2ApAXe1mqLj
$string = XPub::toString($child);
```

## Feature test

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;

final class XPubControllerTest extends TestCase
{
    public function test_it_derives_a_bitcoin_address(): void
    {
        $response = $this->postJson('/api/xpub/derive', [
            'extended_key' => 'xpub68Gmy5EdvgibQVfPdqkBBCHxA5htiqg55crXYuXoQRKfDBFA1WEjWgP6LHhwBZeNK1VTsfTFUHCdrfp1bgwQ9xv5ski8PX9rL2dZXvgGDnw',
            'coin' => 'btc',
            'path' => [1],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('address', '1JQheacLPdM5ySCkrZkV66G2ApAXe1mqLj')
            ->assertJsonStructure(['address', 'extended_key']);
    }

    public function test_it_rejects_unsupported_schemes(): void
    {
        $response = $this->postJson('/api/xpub/derive', [
            'extended_key' => 'zpub6rFR7y4Q2AijBEqTUquhVz398htDFrtymD9xYYfG1m4wAcvPhXNfE3EfH1r1ADqtfSdVCToUG868RvUUkgDKf31mGDtKsAYz2oz2AGutZYs',
            'coin' => 'doge',
            'path' => [0, 0],
        ]);

        $response->assertStatus(422);
    }
}
```

## Error handling

| Exception | Typical cause | HTTP status |
|-----------|---------------|-------------|
| `InvalidXPubException` | Bad Base58, length, checksum, or public key | 422 |
| `InvalidDerivationPathException` | Hardened, negative, or out-of-range index | 422 |
| `UnsupportedAddressSchemeException` | BIP49/BIP84 for DOGE, BCH, or DASH | 422 |
| `UnsupportedCoinException` | Unknown coin in the registry | 422 |
| `UnsupportedVersionException` | Unknown extended key version | 422 |
| `DerivationException` | Invalid derived point (extremely rare) | 422 |

## Notes

- All derivation is public (non-hardened). Hardened paths are rejected.
- BCH returns CashAddr addresses; DOGE and DASH only support P2PKH.
- ETH addresses are EIP-55 checksummed.
- TRON addresses are Base58Check encoded with the `0x41` prefix.
- The service is stateless and safe to reuse across requests.
