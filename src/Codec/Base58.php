<?php

declare(strict_types=1);

namespace Ud5495\XPub\Codec;

use Ud5495\XPub\Exception\InvalidXPubException;
use StephenHill\Base58 as StephenHillBase58;

final class Base58
{
    private ?StephenHillBase58 $codec = null;

    public function encode(string $bytes): string
    {
        return $this->codec()->encode($bytes);
    }

    public function decode(string $encoded): string
    {
        try {
            return $this->codec()->decode($encoded);
        } catch (\Throwable $exception) {
            throw new InvalidXPubException('Invalid Base58 string.', 0, $exception);
        }
    }

    private function codec(): StephenHillBase58
    {
        return $this->codec ??= new StephenHillBase58();
    }
}
