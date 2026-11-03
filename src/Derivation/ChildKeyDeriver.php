<?php

declare(strict_types=1);

namespace Bedud5495\XPub\Derivation;

use Bedud5495\XPub\ChildNumber;
use Bedud5495\XPub\Crypto\Hasher;
use Bedud5495\XPub\Crypto\Secp256k1;
use Bedud5495\XPub\Exception\DerivationException;
use Bedud5495\XPub\ExtendedKey;
use Bedud5495\XPub\PublicKey;
use phpseclib3\Math\BigInteger;

final class ChildKeyDeriver
{
    public function __construct(
        private readonly Secp256k1 $curve,
        private readonly Hasher $hasher,
    ) {
    }

    public function derive(ExtendedKey $parent, ChildNumber $child): ExtendedKey
    {
        $index = $child->value();
        $data = $parent->publicKey->bytes() . $child->toSer32();
        $hmac = hash_hmac('sha512', $data, $this->chainCodeBytes($parent->chainCode), true);

        $left = substr($hmac, 0, 32);
        $right = substr($hmac, 32, 32);
        $scalar = new BigInteger(bin2hex($left), 16);

        if ($scalar->equals(new BigInteger(0)) || $scalar->compare($this->curve->order()) >= 0) {
            throw new DerivationException('Derived key is invalid: IL is zero or greater than the curve order.');
        }

        $parentPoint = $this->curve->decodeCompressed($parent->publicKey->hex());
        $childPoint = $this->curve->addPoints($parentPoint, $this->curve->multiplyGenerator($scalar));

        $fingerprint = substr($this->hasher->hash160($parent->publicKey->hex()), 0, 8);

        return new ExtendedKey(
            $parent->version,
            $parent->depth + 1,
            $fingerprint,
            $index,
            bin2hex($right),
            PublicKey::fromHex($this->curve->encodeCompressed($childPoint)),
        );
    }

    private function chainCodeBytes(string $chainCode): string
    {
        $bytes = hex2bin($chainCode);

        if ($bytes === false) {
            throw new DerivationException('Unable to decode chain code.');
        }

        return $bytes;
    }
}
