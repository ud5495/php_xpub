<?php

declare(strict_types=1);

namespace Bedud5495\XPub\Codec;

use Bedud5495\XPub\Crypto\Hasher;
use Bedud5495\XPub\Crypto\Secp256k1;
use Bedud5495\XPub\Exception\InvalidXPubException;
use Bedud5495\XPub\ExtendedKey;
use Bedud5495\XPub\PublicKey;
use Bedud5495\XPub\Scheme;
use Bedud5495\XPub\Version;

final class ExtendedKeyCodec
{
    private const SERIALIZED_LENGTH = 82;

    public function __construct(
        private readonly Base58 $base58,
        private readonly Hasher $hasher,
        private readonly Secp256k1 $curve,
    ) {
    }

    public function decode(string $encoded, ?Scheme $scheme = null): ExtendedKey
    {
        return $this->decodeBinary($this->base58->decode($encoded), $scheme);
    }

    public function decodeHex(string $hex, ?Scheme $scheme = null): ExtendedKey
    {
        $hex = strtolower(trim($hex));

        if (preg_match('/^[0-9a-f]+$/', $hex) !== 1 || strlen($hex) !== self::SERIALIZED_LENGTH * 2) {
            throw new InvalidXPubException('Extended key hex must contain 82 bytes.');
        }

        $binary = hex2bin($hex);

        if ($binary === false) {
            throw new InvalidXPubException('Unable to decode extended key hex.');
        }

        return $this->decodeBinary($binary, $scheme);
    }

    public function encode(ExtendedKey $key): string
    {
        $binary = hex2bin($this->encodeHex($key));

        if ($binary === false) {
            throw new InvalidXPubException('Unable to encode extended key.');
        }

        return $this->base58->encode($binary);
    }

    public function encodeHex(ExtendedKey $key): string
    {
        $payload = $this->payload($key);
        $checksum = substr($this->hasher->doubleSha256($payload), 0, 8);

        return $payload . $checksum;
    }

    private function decodeBinary(string $binary, ?Scheme $scheme): ExtendedKey
    {
        if (strlen($binary) !== self::SERIALIZED_LENGTH) {
            throw new InvalidXPubException('Extended key must be 82 bytes long.');
        }

        $payload = substr($binary, 0, 78);
        $checksum = substr($binary, 78, 4);

        if (substr($this->hasher->doubleSha256(bin2hex($payload)), 0, 8) !== bin2hex($checksum)) {
            throw new InvalidXPubException('Invalid extended key checksum.');
        }

        $detected = Version::fromHex(bin2hex(substr($binary, 0, 4)));
        $version = $scheme === null ? $detected : Version::forScheme($scheme, $detected->network());

        $depth = ord($binary[4]);
        $parentFingerprint = bin2hex(substr($binary, 5, 4));

        $unpackedIndex = unpack('N', substr($binary, 9, 4));

        if ($unpackedIndex === false || !isset($unpackedIndex[1])) {
            throw new InvalidXPubException('Unable to decode extended key index.');
        }

        $index = $unpackedIndex[1];
        $chainCode = bin2hex(substr($binary, 13, 32));
        $publicKey = PublicKey::fromHex(bin2hex(substr($binary, 45, 33)));

        // Verify that the public key is a valid secp256k1 point.
        $this->curve->decodeCompressed($publicKey->hex());

        return new ExtendedKey($version, $depth, $parentFingerprint, $index, $chainCode, $publicKey);
    }

    private function payload(ExtendedKey $key): string
    {
        return $key->version->hex()
            . str_pad(dechex($key->depth), 2, '0', STR_PAD_LEFT)
            . $key->parentFingerprint
            . str_pad(dechex($key->index), 8, '0', STR_PAD_LEFT)
            . $key->chainCode
            . $key->publicKey->hex();
    }
}
