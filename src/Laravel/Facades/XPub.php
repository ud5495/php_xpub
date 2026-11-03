<?php

declare(strict_types=1);

namespace Ud5495\XPub\Laravel\Facades;

use Ud5495\XPub\Coin;
use Ud5495\XPub\DerivationPath;
use Ud5495\XPub\ExtendedKey;
use Ud5495\XPub\Scheme;
use Ud5495\XPub\Version;
use Ud5495\XPub\XPubService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static ExtendedKey fromString(string $encoded, ?Scheme $scheme = null)
 * @method static ExtendedKey fromHex(string $hex, ?Scheme $scheme = null)
 * @method static ExtendedKey derive(ExtendedKey $key, int|array<int, int>|DerivationPath $path)
 * @method static string toAddress(ExtendedKey $key, Coin $coin = Coin::BTC)
 * @method static string toString(ExtendedKey $key, bool $asHex = false)
 * @method static ExtendedKey withVersion(ExtendedKey $key, Version|string $version)
 * @method static string hash160(string $hex)
 * @method static string doubleSha256(string $hex)
 *
 * @see \Ud5495\XPub\XPubService
 */
final class XPub extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return XPubService::class;
    }
}
