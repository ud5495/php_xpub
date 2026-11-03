<?php

declare(strict_types=1);

namespace Bedud5495\XPub\Address;

use Bedud5495\XPub\Codec\RippleBase58;
use Bedud5495\XPub\Coin;
use Bedud5495\XPub\Crypto\Hasher;
use Bedud5495\XPub\Exception\InvalidXPubException;
use Bedud5495\XPub\ExtendedKey;

final class XrpAddressGenerator implements AddressGenerator
{
    private const ADDRESS_TYPE = '00';

    public function __construct(
        private readonly Hasher $hasher,
        private readonly RippleBase58 $base58,
    ) {
    }

    public function supports(Coin $coin): bool
    {
        return $coin === Coin::XRP;
    }

    public function generate(ExtendedKey $key): string
    {
        $payload = self::ADDRESS_TYPE . $this->hasher->hash160($key->publicKey->hex());
        $checksum = substr($this->hasher->doubleSha256($payload), 0, 8);
        $binary = hex2bin($payload . $checksum);

        if ($binary === false) {
            throw new InvalidXPubException('Unable to encode XRP address.');
        }

        return $this->base58->encode($binary);
    }
}
