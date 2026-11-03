<?php

declare(strict_types=1);

namespace Ud5495\XPub;

use Ud5495\XPub\Exception\InvalidDerivationPathException;

final class ChildNumber
{
    private const MAX_NON_HARDENED = 0x7FFFFFFF;

    private function __construct(private readonly int $value)
    {
    }

    public static function fromInt(int $value): self
    {
        if ($value < 0 || $value > self::MAX_NON_HARDENED) {
            throw new InvalidDerivationPathException(
                'Child index must be between 0 and 2147483647; hardened derivation is not supported.'
            );
        }

        return new self($value);
    }

    public function value(): int
    {
        return $this->value;
    }

    public function toSer32(): string
    {
        return pack('N', $this->value);
    }
}
