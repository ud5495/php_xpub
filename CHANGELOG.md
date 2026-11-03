# Changelog

All notable changes to this project are documented in this file.

## 1.2.0

- Add TRON (`Coin::TRON`, value `tron`) address generation using Base58Check with the `0x41`
  prefix over the Keccak-256 public key hash.
- Register `TronAddressGenerator` by default and document TRON in the README and Laravel guide.

## 1.1.1

- Fix CashAddr checksum generation: the prefix is expanded using only the lower five bits of
  each character, as required by the CashAddr specification. This corrects all BCH addresses.
- Add regression tests using the official CashAddr specification vectors.

## 1.0.0

- Initial release of `bedud5495/php_xpub`.
- Service-oriented rewrite with immutable `ExtendedKey` values and enums.
- Supports BIP44/BIP49/BIP84 extended keys (xpub/tpub/ypub/upub/zpub/vpub).
- Derives BTC, LTC, DOGE, BCH, DASH and ETH addresses.
- BCH uses CashAddr; LTC supports P2PKH, nested SegWit and native SegWit.
- DOGE and DASH support P2PKH; unsupported SegWit schemes throw an exception.
- Requires only BCMath; GMP is optional.
- Laravel service provider and facade with package auto-discovery.
- Laravel guide with Form Request, controller, route, exception handling and feature test.
- All domain exceptions implement `XPubExceptionInterface`.
- PHPUnit parity test suite and PHPStan level 8.
