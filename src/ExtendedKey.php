<?php

declare(strict_types=1);

namespace Bedud5495\XPub;

use Bedud5495\XPub\Exception\InvalidXPubException;

final class ExtendedKey
{
    public function __construct(
        public readonly Version $version,
        public readonly int $depth,
        public readonly string $parentFingerprint,
        public readonly int $index,
        public readonly string $chainCode,
        public readonly PublicKey $publicKey,
    ) {
        if ($depth < 0 || $depth > 255) {
            throw new InvalidXPubException('Extended key depth must be between 0 and 255.');
        }

        if (preg_match('/^[0-9a-f]{8}$/', $parentFingerprint) !== 1) {
            throw new InvalidXPubException('Parent fingerprint must be 4 bytes encoded as hex.');
        }

        if ($index < 0 || $index > 0xFFFFFFFF) {
            throw new InvalidXPubException('Extended key index must be an unsigned 32-bit integer.');
        }

        if (preg_match('/^[0-9a-f]{64}$/', $chainCode) !== 1) {
            throw new InvalidXPubException('Chain code must be 32 bytes encoded as hex.');
        }
    }

    public function network(): Network
    {
        return $this->version->network();
    }

    public function withVersion(Version $version): self
    {
        return new self(
            $version,
            $this->depth,
            $this->parentFingerprint,
            $this->index,
            $this->chainCode,
            $this->publicKey,
        );
    }
}
