<?php

declare(strict_types=1);

namespace Bedud5495\XPub;

use Bedud5495\XPub\Exception\InvalidXPubException;

final class PublicKey
{
    private function __construct(private readonly string $hex)
    {
    }

    public static function fromHex(string $hex): self
    {
        $hex = strtolower($hex);

        if (preg_match('/^(02|03)[0-9a-f]{64}$/', $hex) !== 1) {
            throw new InvalidXPubException('Invalid compressed secp256k1 public key.');
        }

        return new self($hex);
    }

    public function hex(): string
    {
        return $this->hex;
    }

    public function bytes(): string
    {
        $bytes = hex2bin($this->hex);

        if ($bytes === false) {
            throw new InvalidXPubException('Unable to decode public key hex.');
        }

        return $bytes;
    }
}
