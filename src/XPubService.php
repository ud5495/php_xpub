<?php

declare(strict_types=1);

namespace Bedud5495\XPub;

use Bedud5495\XPub\Address\AddressGeneratorRegistry;
use Bedud5495\XPub\Address\EthereumAddressGenerator;
use Bedud5495\XPub\Address\UtxoAddressGenerator;
use Bedud5495\XPub\Codec\Base58;
use Bedud5495\XPub\Codec\Bech32;
use Bedud5495\XPub\Codec\CashAddr;
use Bedud5495\XPub\Codec\ExtendedKeyCodec;
use Bedud5495\XPub\Crypto\Hasher;
use Bedud5495\XPub\Crypto\Secp256k1;
use Bedud5495\XPub\Derivation\ChildKeyDeriver;
use Bedud5495\XPub\Exception\UnsupportedVersionException;

final class XPubService
{
    public function __construct(
        private readonly ExtendedKeyCodec $codec,
        private readonly ChildKeyDeriver $deriver,
        private readonly AddressGeneratorRegistry $addressGenerators,
        private readonly Hasher $hasher,
    ) {
    }

    public static function withDefaults(): self
    {
        $base58 = new Base58();
        $bech32 = new Bech32();
        $cashAddr = new CashAddr();
        $hasher = new Hasher();
        $curve = new Secp256k1();

        return new self(
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
    }

    public function fromString(string $encoded, ?Scheme $scheme = null): ExtendedKey
    {
        return $this->codec->decode($encoded, $scheme);
    }

    public function fromHex(string $hex, ?Scheme $scheme = null): ExtendedKey
    {
        return $this->codec->decodeHex($hex, $scheme);
    }

    /**
     * @param int|array<int, int>|DerivationPath $path
     */
    public function derive(ExtendedKey $key, int|array|DerivationPath $path): ExtendedKey
    {
        foreach (DerivationPath::from($path) as $child) {
            $key = $this->deriver->derive($key, $child);
        }

        return $key;
    }

    public function toAddress(ExtendedKey $key, Coin $coin = Coin::BTC): string
    {
        return $this->addressGenerators->generate($key, $coin);
    }

    public function toString(ExtendedKey $key, bool $asHex = false): string
    {
        return $asHex ? $this->codec->encodeHex($key) : $this->codec->encode($key);
    }

    public function withVersion(ExtendedKey $key, Version|string $version): ExtendedKey
    {
        if (is_string($version)) {
            $version = Version::tryFrom(strtolower($version))
                ?? throw new UnsupportedVersionException(sprintf('Unsupported extended key version "%s".', $version));
        }

        return $key->withVersion($version);
    }

    public function hash160(string $hex): string
    {
        return $this->hasher->hash160($hex);
    }

    public function doubleSha256(string $hex): string
    {
        return $this->hasher->doubleSha256($hex);
    }
}
