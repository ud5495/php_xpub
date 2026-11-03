<?php

declare(strict_types=1);

namespace Bedud5495\XPub\Codec;

use Bedud5495\XPub\Exception\InvalidXPubException;

/**
 * Bitcoin Cash CashAddr encoder.
 *
 * @see https://github.com/bitcoincashorg/bitcoincash.org/blob/master/spec/cashaddr.md
 */
final class CashAddr
{
    private const CHARSET = 'qpzry9x8gf2tvdw0s3jn54khce6mua7l';

    private const GENERATOR = [
        0x98F2BC8E61,
        0x79B76D99E2,
        0xF33E5FB3C4,
        0xAE2EABE2A8,
        0x1E4F43E470,
    ];

    private const SIZE_BITS = [
        20 => 0,
        24 => 1,
        28 => 2,
        32 => 3,
        40 => 4,
        48 => 5,
        56 => 6,
        64 => 7,
    ];

    public function encode(string $prefix, int $type, string $hash): string
    {
        $length = strlen($hash);

        if (!isset(self::SIZE_BITS[$length])) {
            throw new InvalidXPubException('Unsupported CashAddr hash size.');
        }

        if ($type !== 0 && $type !== 1) {
            throw new InvalidXPubException('Unsupported CashAddr type.');
        }

        $version = ($type << 3) | self::SIZE_BITS[$length];
        $payload = $this->convertBits(chr($version) . $hash);
        $checksum = $this->checksum($prefix, $payload);

        $encoded = '';

        foreach (array_merge($payload, $checksum) as $value) {
            $encoded .= self::CHARSET[$value];
        }

        return strtolower($prefix) . ':' . $encoded;
    }

    /**
     * @return list<int>
     */
    private function convertBits(string $bytes): array
    {
        $accumulator = 0;
        $bits = 0;
        $result = [];

        foreach (unpack('C*', $bytes) ?: [] as $byte) {
            $accumulator = (($accumulator << 8) | $byte) & 0x0FFF;
            $bits += 8;

            while ($bits >= 5) {
                $bits -= 5;
                $result[] = ($accumulator >> $bits) & 0x1F;
            }
        }

        if ($bits > 0) {
            $result[] = ($accumulator << (5 - $bits)) & 0x1F;
        }

        return $result;
    }

    /**
     * @param list<int> $payload
     *
     * @return list<int>
     */
    private function checksum(string $prefix, array $payload): array
    {
        $values = array_merge(
            $this->prefixExpand($prefix),
            $payload,
            [0, 0, 0, 0, 0, 0, 0, 0],
        );

        $polymod = $this->polymod($values);
        $checksum = [];

        for ($index = 0; $index < 8; $index++) {
            $checksum[] = ($polymod >> (5 * (7 - $index))) & 0x1F;
        }

        return $checksum;
    }

    /**
     * @return list<int>
     */
    private function prefixExpand(string $prefix): array
    {
        $prefix = strtolower($prefix);

        $result = [];

        foreach (str_split($prefix) as $character) {
            $result[] = ord($character) & 0x1F;
        }

        $result[] = 0;

        return $result;
    }

    /**
     * @param list<int> $values
     */
    private function polymod(array $values): int
    {
        $checksum = 1;

        foreach ($values as $value) {
            $top = $checksum >> 35;
            $checksum = (($checksum & 0x07FFFFFFFF) << 5) ^ $value;

            for ($bit = 0; $bit < 5; $bit++) {
                if (($top & (1 << $bit)) !== 0) {
                    $checksum ^= self::GENERATOR[$bit];
                }
            }
        }

        return $checksum ^ 1;
    }
}
