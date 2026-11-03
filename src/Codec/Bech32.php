<?php

declare(strict_types=1);

namespace Bedud5495\XPub\Codec;

use Bedud5495\XPub\Exception\InvalidXPubException;

final class Bech32
{
    public function encodeSegwit(string $humanReadablePart, int $version, string $program): string
    {
        try {
            return \BitWasp\Bech32\encodeSegwit($humanReadablePart, $version, $program);
        } catch (\Throwable $exception) {
            throw new InvalidXPubException('Unable to encode SegWit address.', 0, $exception);
        }
    }
}
