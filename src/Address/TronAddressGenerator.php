<?php

declare(strict_types=1);

namespace Ud5495\XPub\Address;

use Ud5495\XPub\Codec\Base58;
use Ud5495\XPub\Coin;
use Ud5495\XPub\Crypto\Hasher;
use Ud5495\XPub\Crypto\Secp256k1;
use Ud5495\XPub\Exception\InvalidXPubException;
use Ud5495\XPub\ExtendedKey;
use kornrunner\Keccak;

final class TronAddressGenerator implements AddressGenerator
{
    private const KECCAK_BITS = 256;
    private const ADDRESS_OFFSET = 24;
    private const ADDRESS_PREFIX = '41';

    public function __construct(
        private readonly Secp256k1 $curve,
        private readonly Base58 $base58,
        private readonly Hasher $hasher,
    ) {
    }

    public function supports(Coin $coin): bool
    {
        return $coin === Coin::TRON;
    }

    public function generate(ExtendedKey $key): string
    {
        $point = $this->curve->decodeCompressed($key->publicKey->hex());
        $uncompressed = hex2bin($this->curve->encodeUncompressed($point));

        if ($uncompressed === false) {
            throw new InvalidXPubException('Unable to build uncompressed public key.');
        }

        $hash = Keccak::hash(substr($uncompressed, 1), self::KECCAK_BITS);
        $payload = self::ADDRESS_PREFIX . substr($hash, self::ADDRESS_OFFSET);
        $checksum = substr($this->hasher->doubleSha256($payload), 0, 8);
        $binary = hex2bin($payload . $checksum);

        if ($binary === false) {
            throw new InvalidXPubException('Unable to encode Tron address.');
        }

        return $this->base58->encode($binary);
    }
}
