<?php

declare(strict_types=1);

namespace Ud5495\XPub\Address;

use Ud5495\XPub\Codec\Base58;
use Ud5495\XPub\Codec\Bech32;
use Ud5495\XPub\Codec\CashAddr;
use Ud5495\XPub\Coin;
use Ud5495\XPub\Crypto\Hasher;
use Ud5495\XPub\Exception\InvalidXPubException;
use Ud5495\XPub\Exception\UnsupportedAddressSchemeException;
use Ud5495\XPub\ExtendedKey;
use Ud5495\XPub\Scheme;

final class UtxoAddressGenerator implements AddressGenerator
{
    private const SEGWIT_VERSION = 0;

    public function __construct(
        private readonly Coin $coin,
        private readonly Base58 $base58,
        private readonly Bech32 $bech32,
        private readonly CashAddr $cashAddr,
        private readonly Hasher $hasher,
    ) {
    }

    public function supports(Coin $coin): bool
    {
        return $coin === $this->coin;
    }

    public function generate(ExtendedKey $key): string
    {
        $parameters = AddressParameters::for($this->coin, $key->network());

        return match ($key->version->scheme()) {
            Scheme::BIP44 => $this->p2pkh($key, $parameters),
            Scheme::BIP49 => $this->p2shNestedSegwit($key, $parameters),
            Scheme::BIP84 => $this->p2wpkh($key, $parameters),
        };
    }

    private function p2pkh(ExtendedKey $key, AddressParameters $parameters): string
    {
        $keyHash = $this->hasher->hash160($key->publicKey->hex());

        if ($parameters->cashAddrPrefix !== null) {
            $binary = hex2bin($keyHash);

            if ($binary === false) {
                throw new InvalidXPubException('Unable to build CashAddr payload.');
            }

            return $this->cashAddr->encode($parameters->cashAddrPrefix, 0, $binary);
        }

        return $this->base58Check($parameters->p2pkhVersion . $keyHash);
    }

    private function p2shNestedSegwit(ExtendedKey $key, AddressParameters $parameters): string
    {
        $this->requireSegwit($parameters, Scheme::BIP49);

        $keyHash = $this->hasher->hash160($key->publicKey->hex());
        $redeemScript = '0014' . $keyHash;
        $payload = $parameters->p2shVersion . $this->hasher->hash160($redeemScript);

        return $this->base58Check($payload);
    }

    private function p2wpkh(ExtendedKey $key, AddressParameters $parameters): string
    {
        $humanReadablePart = $this->requireSegwit($parameters, Scheme::BIP84);
        $program = hex2bin($this->hasher->hash160($key->publicKey->hex()));

        if ($program === false) {
            throw new InvalidXPubException('Unable to build witness program.');
        }

        return $this->bech32->encodeSegwit($humanReadablePart, self::SEGWIT_VERSION, $program);
    }

    private function requireSegwit(AddressParameters $parameters, Scheme $scheme): string
    {
        if ($parameters->segwitHrp === null) {
            throw new UnsupportedAddressSchemeException(
                sprintf('%s addresses are not supported for %s.', $scheme->value, strtoupper($this->coin->value))
            );
        }

        return $parameters->segwitHrp;
    }

    private function base58Check(string $payload): string
    {
        $checksum = substr($this->hasher->doubleSha256($payload), 0, 8);
        $binary = hex2bin($payload . $checksum);

        if ($binary === false) {
            throw new InvalidXPubException('Unable to encode Base58Check address.');
        }

        return $this->base58->encode($binary);
    }
}
