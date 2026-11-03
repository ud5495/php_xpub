<?php

declare(strict_types=1);

namespace Bedud5495\XPub;

use Bedud5495\XPub\Exception\UnsupportedVersionException;

enum Version: string
{
    case XPUB = 'xpub';
    case TPUB = 'tpub';
    case YPUB = 'ypub';
    case UPUB = 'upub';
    case ZPUB = 'zpub';
    case VPUB = 'vpub';

    public function hex(): string
    {
        return match ($this) {
            self::XPUB => '0488b21e',
            self::TPUB => '043587cf',
            self::YPUB => '049d7cb2',
            self::UPUB => '044a5262',
            self::ZPUB => '04b24746',
            self::VPUB => '045f1cf6',
        };
    }

    public function network(): Network
    {
        return match ($this) {
            self::XPUB, self::YPUB, self::ZPUB => Network::MAINNET,
            self::TPUB, self::UPUB, self::VPUB => Network::TESTNET,
        };
    }

    public function scheme(): Scheme
    {
        return match ($this) {
            self::XPUB, self::TPUB => Scheme::BIP44,
            self::YPUB, self::UPUB => Scheme::BIP49,
            self::ZPUB, self::VPUB => Scheme::BIP84,
        };
    }

    public static function fromPrefix(string $prefix): self
    {
        return self::tryFrom(strtolower($prefix))
            ?? throw new UnsupportedVersionException(sprintf('Unsupported extended key version "%s".', $prefix));
    }

    public static function fromHex(string $hex): self
    {
        $hex = strtolower($hex);

        foreach (self::cases() as $version) {
            if ($version->hex() === $hex) {
                return $version;
            }
        }

        throw new UnsupportedVersionException(sprintf('Unsupported extended key version hex "%s".', $hex));
    }

    public static function forScheme(Scheme $scheme, Network $network): self
    {
        return match ($scheme) {
            Scheme::BIP44 => $network === Network::MAINNET ? self::XPUB : self::TPUB,
            Scheme::BIP49 => $network === Network::MAINNET ? self::YPUB : self::UPUB,
            Scheme::BIP84 => $network === Network::MAINNET ? self::ZPUB : self::VPUB,
        };
    }
}
