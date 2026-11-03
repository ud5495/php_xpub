<?php

declare(strict_types=1);

namespace Ud5495\XPub\Crypto;

use Ud5495\XPub\Exception\DerivationException;
use Ud5495\XPub\Exception\InvalidXPubException;
use phpseclib3\Crypt\EC\BaseCurves\Prime;
use phpseclib3\Crypt\EC\Curves\secp256k1 as Secp256k1Curve;
use phpseclib3\Math\BigInteger;
use phpseclib3\Math\PrimeField\Integer as PrimeInteger;

/**
 * Thin adapter around phpseclib's secp256k1 implementation.
 *
 * The adapter keeps the rest of the library independent from phpseclib and
 * makes the curve operations easy to replace in tests.
 */
final class Secp256k1
{
    private ?Prime $curve = null;

    public function order(): BigInteger
    {
        return $this->curve()->getOrder();
    }

    /**
     * @return array{0: PrimeInteger, 1: PrimeInteger}
     */
    public function decodeCompressed(string $hex): array
    {
        $bytes = hex2bin($hex);

        if ($bytes === false) {
            throw new InvalidXPubException('Unable to decode compressed public key.');
        }

        try {
            return $this->normalizePoint($this->curve()->derivePoint($bytes));
        } catch (\Throwable $exception) {
            throw new InvalidXPubException('Public key is not a valid secp256k1 point.', 0, $exception);
        }
    }

    /**
     * @return array{0: PrimeInteger, 1: PrimeInteger}
     */
    public function multiplyGenerator(BigInteger $scalar): array
    {
        return $this->normalizePoint(
            $this->curve()->multiplyPoint($this->curve()->getBasePoint(), $scalar)
        );
    }

    /**
     * @param array{0: PrimeInteger, 1: PrimeInteger} $left
     * @param array{0: PrimeInteger, 1: PrimeInteger} $right
     *
     * @return array{0: PrimeInteger, 1: PrimeInteger}
     */
    public function addPoints(array $left, array $right): array
    {
        $sum = $this->curve()->addPoint($left, $right);

        if ($sum === []) {
            throw new DerivationException('Derived public key is the point at infinity.');
        }

        return $this->normalizePoint($sum);
    }

    /**
     * @param array{0: PrimeInteger, 1: PrimeInteger} $point
     */
    public function encodeCompressed(array $point): string
    {
        $prefix = $point[1]->isOdd() ? '03' : '02';

        return $prefix . bin2hex($point[0]->toBytes());
    }

    /**
     * @param array{0: PrimeInteger, 1: PrimeInteger} $point
     */
    public function encodeUncompressed(array $point): string
    {
        return '04' . bin2hex($point[0]->toBytes()) . bin2hex($point[1]->toBytes());
    }

    private function curve(): Prime
    {
        return $this->curve ??= new Secp256k1Curve();
    }

    /**
     * @return array{0: PrimeInteger, 1: PrimeInteger}
     */
    private function normalizePoint(mixed $point): array
    {
        if (
            !is_array($point)
            || !isset($point[0], $point[1])
            || !$point[0] instanceof PrimeInteger
            || !$point[1] instanceof PrimeInteger
        ) {
            throw new InvalidXPubException('Invalid secp256k1 point.');
        }

        return [$point[0], $point[1]];
    }
}
