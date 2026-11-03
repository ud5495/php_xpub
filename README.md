# php_xpub

[![tests](https://github.com/bedud5495/php_xpub/actions/workflows/php.yml/badge.svg)](https://github.com/bedud5495/php_xpub/actions/workflows/php.yml)

Derive BTC, LTC, DOGE, BCH, DASH and ETH extended public keys and addresses from
xpub/tpub/ypub/upub/zpub/vpub keys.

The library only requires the BCMath extension. GMP is used automatically for faster big-integer
arithmetic when it is available, but it is not required.

## Supported formats

| Type                  | Mainnet | Testnet |
|-----------------------|---------|---------|
| BIP44 (standard)      | xpub    | tpub    |
| BIP49 (nested SegWit) | ypub    | upub    |
| BIP84 (native SegWit) | zpub    | vpub    |

## Requirements

- PHP 8.1 – 8.5
- `ext-bcmath`
- `ext-gmp` is optional and only used for performance

## Installation

```bash
composer require bedud5495/php_xpub
```

## Usage

```php
use Bedud5495\XPub\Coin;
use Bedud5495\XPub\Scheme;
use Bedud5495\XPub\Version;
use Bedud5495\XPub\XPubService;

$service = XPubService::withDefaults();

// Parse any supported extended key. The format is detected automatically.
$key = $service->fromString('xpub...');

// You can override the address scheme when the source prefix does not match it.
$key = $service->fromString('xpub...', Scheme::BIP84);

// Derive a single child or a full path.
$child = $service->derive($key, 0);
$grandChild = $service->derive($key, [0, 1]);

// Serialize back to Base58 or hex.
$string = $service->toString($grandChild);
$hex = $service->toString($grandChild, true);

// Generate an address. BTC is the default.
$btcAddress = $service->toAddress($grandChild, Coin::BTC);
$ltcAddress = $service->toAddress($grandChild, Coin::LTC);
$dogeAddress = $service->toAddress($grandChild, Coin::DOGE);
$bchAddress = $service->toAddress($grandChild, Coin::BCH);
$dashAddress = $service->toAddress($grandChild, Coin::DASH);
$ethAddress = $service->toAddress($grandChild, Coin::ETH);

// Convert between supported versions without changing the key material.
$zpub = $service->withVersion($key, Version::ZPUB);
```

### Parsing hex

```php
$key = $service->fromHex('0488b21e...');
```

### Hashing helpers

```php
$hash160 = $service->hash160($publicKeyHex);
$checksum = $service->doubleSha256($dataHex);
```

### Address formats

| Coin | BIP44 | BIP49 | BIP84 |
|------|-------|-------|-------|
| BTC  | P2PKH | P2SH-wrapped SegWit | native SegWit (bech32) |
| LTC  | P2PKH | P2SH-wrapped SegWit | native SegWit (bech32) |
| DOGE | P2PKH | not supported | not supported |
| BCH  | CashAddr (P2PKH) | not supported | not supported |
| DASH | P2PKH | not supported | not supported |
| ETH  | EIP-55 checksummed address | – | – |

Unsupported combinations throw `UnsupportedAddressSchemeException`.

## Laravel

The package is auto-discovered. Inject `XPubService` or use the `XPub` facade:

```php
use Bedud5495\XPub\Coin;
use Bedud5495\XPub\Laravel\Facades\XPub;

$key = XPub::fromString('xpub...');
$address = XPub::toAddress(XPub::derive($key, [0, 0]), Coin::BTC);
```

See [docs/laravel.md](docs/laravel.md) for a complete example with Form Request validation,
a controller, routes, exception handling, and a feature test.
```

## Custom construction

`XPubService::withDefaults()` wires all adapters. If you need a different base58, bech32 or curve
implementation, construct the service with your own instances:

```php
use Bedud5495\XPub\Address\AddressGeneratorRegistry;
use Bedud5495\XPub\Address\EthereumAddressGenerator;
use Bedud5495\XPub\Address\UtxoAddressGenerator;
use Bedud5495\XPub\Codec\Base58;
use Bedud5495\XPub\Codec\Bech32;
use Bedud5495\XPub\Codec\CashAddr;
use Bedud5495\XPub\Codec\ExtendedKeyCodec;
use Bedud5495\XPub\Coin;
use Bedud5495\XPub\Crypto\Hasher;
use Bedud5495\XPub\Crypto\Secp256k1;
use Bedud5495\XPub\Derivation\ChildKeyDeriver;
use Bedud5495\XPub\XPubService;

$base58 = new Base58();
$bech32 = new Bech32();
$cashAddr = new CashAddr();
$hasher = new Hasher();
$curve = new Secp256k1();

$service = new XPubService(
    new ExtendedKeyCodec($base58, $hasher, $curve),
    new ChildKeyDeriver($curve, $hasher),
    new AddressGeneratorRegistry([
        new UtxoAddressGenerator(Coin::BTC, $base58, $bech32, $cashAddr, $hasher),
        new UtxoAddressGenerator(Coin::LTC, $base58, $bech32, $cashAddr, $hasher),
        new UtxoAddressGenerator(Coin::DOGE, $base58, $bech32, $cashAddr, $hasher),
        new UtxoAddressGenerator(Coin::BCH, $base58, $bech32, $cashAddr, $hasher),
        new UtxoAddressGenerator(Coin::DASH, $base58, $bech32, $cashAddr, $hasher),
        new EthereumAddressGenerator($curve),
    ]),
    $hasher,
);
```

## Development

```bash
composer install
composer test
composer analyse
```

## Limitations

- Only public (non-hardened) derivation is supported, as required for extended public keys.
- Deriving with a hardened index throws `InvalidDerivationPathException`.
- Conversions preserve the key material; they only change the serialization prefix and the
  default address scheme.
