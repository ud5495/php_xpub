# php_xpub

[![tests](https://github.com/ud5495/php_xpub/actions/workflows/php.yml/badge.svg)](https://github.com/ud5495/php_xpub/actions/workflows/php.yml)

Derive BTC, LTC, DOGE, BCH, DASH, ETH, TRON and XRP extended public keys and addresses from
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
composer require ud5495/php_xpub
```

> **Upgrading from 1.x?** Version 2.0.0 moved the package to `ud5495/php_xpub` and the namespace to
> `Ud5495\XPub`. Update your `composer require` line and every `use` statement accordingly.

## Usage

```php
use Ud5495\XPub\Coin;
use Ud5495\XPub\Scheme;
use Ud5495\XPub\Version;
use Ud5495\XPub\XPubService;

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
$tronAddress = $service->toAddress($grandChild, Coin::TRON);
$xrpAddress = $service->toAddress($grandChild, Coin::XRP);

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
| TRON | Base58Check address (0x41 prefix) | – | – |
| XRP  | Ripple Base58Check address (0x00 version) | – | – |

Unsupported combinations throw `UnsupportedAddressSchemeException`.

## Laravel

The package is auto-discovered. Inject `XPubService` or use the `XPub` facade:

```php
use Ud5495\XPub\Coin;
use Ud5495\XPub\Laravel\Facades\XPub;

$key = XPub::fromString('xpub...');
$address = XPub::toAddress(XPub::derive($key, [0, 0]), Coin::BTC);
```

See [docs/laravel.md](docs/laravel.md) for a complete example with Form Request validation,
a controller, routes, exception handling, and a feature test.

## Supported coins

| Coin | Enum value | Derivation | Address format |
|------|------------|------------|----------------|
| Bitcoin       | `Coin::BTC`  | BIP44 / BIP49 / BIP84 | P2PKH, P2SH-wrapped SegWit, bech32 |
| Litecoin      | `Coin::LTC`  | BIP44 / BIP49 / BIP84 | P2PKH, P2SH-wrapped SegWit, bech32 |
| Dogecoin      | `Coin::DOGE` | BIP44 | P2PKH |
| Bitcoin Cash  | `Coin::BCH`  | BIP44 | CashAddr |
| Dash          | `Coin::DASH` | BIP44 | P2PKH |
| Ethereum      | `Coin::ETH`  | BIP44 (`m/44'/60'/0'/0`) | EIP-55 |
| TRON          | `Coin::TRON` | BIP44 (`m/44'/195'/0'/0`) | Base58Check (`0x41` prefix) |
| XRP           | `Coin::XRP`  | BIP44 (`m/44'/144'/0'/0`) | Ripple Base58Check (`0x00` version) |

### TRON example

```php
use Ud5495\XPub\Coin;
use Ud5495\XPub\Laravel\Facades\XPub;

$xpub = 'xpub6Dw3psxyMo73ox5g6HHLfHsp8j4K66fJmWYngSL3EucDshiU9mjmsFACX3M1TetKpApAEwvv3jNLFMg4PGSeDKioNVrAZfaVm6yaYGsMap9';
$key = XPub::fromString($xpub);

$address0 = XPub::toAddress(XPub::derive($key, 0), Coin::TRON);
// TFAnkgRx4wBdJLLxgcRwVvF4vkVGsxQnhi

$address1 = XPub::toAddress(XPub::derive($key, 1), Coin::TRON);
// TV5nWufu5Z1JrCb7byD8eLrp76vo3ZXy3w
```

### XRP example

```php
use Ud5495\XPub\Coin;
use Ud5495\XPub\Laravel\Facades\XPub;

$xpub = 'xpub6Duw1vrZFioQHUpKTEKMYNRkmHAJHFnXk2KaferFUvDwzSXvSY2deXJVJT1eatsHamQW4Rp2Dm3Y4vGuPu7nLxqgSAixg7xUQ37kB1VUR97';
$key = XPub::fromString($xpub);

$address0 = XPub::toAddress(XPub::derive($key, 0), Coin::XRP);
// rN7LCRMygHWWUEnDATAFrLCcGVQMgVm3gW

$address1 = XPub::toAddress(XPub::derive($key, 1), Coin::XRP);
// rwZJsg29pRw7H1925QmKau52RKLoimfDdc
```

## Custom construction

`XPubService::withDefaults()` wires all adapters. If you need a different base58, bech32 or curve
implementation, construct the service with your own instances:

```php
use Ud5495\XPub\Address\AddressGeneratorRegistry;
use Ud5495\XPub\Address\EthereumAddressGenerator;
use Ud5495\XPub\Address\TronAddressGenerator;
use Ud5495\XPub\Address\UtxoAddressGenerator;
use Ud5495\XPub\Address\XrpAddressGenerator;
use Ud5495\XPub\Codec\Base58;
use Ud5495\XPub\Codec\Bech32;
use Ud5495\XPub\Codec\CashAddr;
use Ud5495\XPub\Codec\ExtendedKeyCodec;
use Ud5495\XPub\Codec\RippleBase58;
use Ud5495\XPub\Coin;
use Ud5495\XPub\Crypto\Hasher;
use Ud5495\XPub\Crypto\Secp256k1;
use Ud5495\XPub\Derivation\ChildKeyDeriver;
use Ud5495\XPub\XPubService;

$base58 = new Base58();
$bech32 = new Bech32();
$cashAddr = new CashAddr();
$rippleBase58 = new RippleBase58();
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
        new TronAddressGenerator($curve, $base58, $hasher),
        new XrpAddressGenerator($hasher, $rippleBase58),
    ]),
    $hasher,
);
```

## Changelog

Packagist renders this README (not `CHANGELOG.md`), so recent releases are summarized here.
See [CHANGELOG.md](CHANGELOG.md) for the full history.

### 2.0.0

- **Breaking:** package renamed to `ud5495/php_xpub` and namespace to `Ud5495\XPub`.
- Repository moved to <https://github.com/ud5495/php_xpub>.

### 1.2.2

- Added XRP address generation (`Coin::XRP`, value `xrp`).
- Package description now includes TRON and XRP.
- Changelog surfaced in this README.

### 1.2.1

- Documented every supported coin and added a verified TRON example.
- Added address vectors for every `Coin` case and a regression test covering all enum values.

### 1.2.0

- Added TRON address generation (`Coin::TRON`, value `tron`).

### 1.1.1

- Fixed CashAddr checksum prefix expansion.

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
