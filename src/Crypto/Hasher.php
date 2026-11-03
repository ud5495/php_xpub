<?php

declare(strict_types=1);

namespace Ud5495\XPub\Crypto;

use Ud5495\XPub\Exception\InvalidXPubException;

final class Hasher
{
    public function hash160(string $hex): string
    {
        return hash('ripemd160', hash('sha256', $this->binary($hex), true));
    }

    public function doubleSha256(string $hex): string
    {
        return hash('sha256', hash('sha256', $this->binary($hex), true));
    }

    private function binary(string $hex): string
    {
        if (preg_match('/^[0-9a-f]*$/', $hex) !== 1 || strlen($hex) % 2 !== 0) {
            throw new InvalidXPubException('Invalid hex input.');
        }

        $binary = hex2bin($hex);

        if ($binary === false) {
            throw new InvalidXPubException('Unable to decode hex input.');
        }

        return $binary;
    }
}
