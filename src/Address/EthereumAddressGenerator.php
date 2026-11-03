<?php

declare(strict_types=1);

namespace Ud5495\XPub\Address;

use Ud5495\XPub\Coin;
use Ud5495\XPub\Crypto\Secp256k1;
use Ud5495\XPub\Exception\InvalidXPubException;
use Ud5495\XPub\ExtendedKey;
use kornrunner\Keccak;

final class EthereumAddressGenerator implements AddressGenerator
{
    private const KECCAK_BITS = 256;
    private const ADDRESS_OFFSET = 24;

    public function __construct(private readonly Secp256k1 $curve)
    {
    }

    public function supports(Coin $coin): bool
    {
        return $coin === Coin::ETH;
    }

    public function generate(ExtendedKey $key): string
    {
        $point = $this->curve->decodeCompressed($key->publicKey->hex());
        $uncompressed = hex2bin($this->curve->encodeUncompressed($point));

        if ($uncompressed === false) {
            throw new InvalidXPubException('Unable to build uncompressed public key.');
        }

        $hash = Keccak::hash(substr($uncompressed, 1), self::KECCAK_BITS);
        $address = substr($hash, self::ADDRESS_OFFSET);

        return '0x' . $this->checksum($address);
    }

    private function checksum(string $address): string
    {
        $hash = Keccak::hash($address, self::KECCAK_BITS);
        $checksummed = '';

        foreach (str_split($address) as $index => $character) {
            $checksummed .= preg_match('/[a-f]/', $character) === 1
                ? (hexdec($hash[$index]) >= 8 ? strtoupper($character) : strtolower($character))
                : $character;
        }

        return $checksummed;
    }
}
