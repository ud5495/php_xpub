<?php

declare(strict_types=1);

namespace Bedud5495\XPub\Address;

use Bedud5495\XPub\Coin;
use Bedud5495\XPub\Exception\UnsupportedCoinException;
use Bedud5495\XPub\Network;

/**
 * Version bytes and human-readable parts used to encode UTXO addresses.
 */
final class AddressParameters
{
    private function __construct(
        public readonly string $p2pkhVersion,
        public readonly string $p2shVersion,
        public readonly ?string $segwitHrp,
        public readonly ?string $cashAddrPrefix,
    ) {
    }

    public static function for(Coin $coin, Network $network): self
    {
        $mainnet = $network === Network::MAINNET;

        return match ($coin) {
            Coin::BTC => new self(
                $mainnet ? '00' : '6f',
                $mainnet ? '05' : 'c4',
                $mainnet ? 'bc' : 'tb',
                null,
            ),
            Coin::LTC => new self(
                $mainnet ? '30' : '6f',
                $mainnet ? '32' : '3a',
                $mainnet ? 'ltc' : 'tltc',
                null,
            ),
            Coin::DOGE => new self(
                $mainnet ? '1e' : '71',
                $mainnet ? '16' : 'c4',
                null,
                null,
            ),
            Coin::BCH => new self(
                $mainnet ? '00' : '6f',
                $mainnet ? '05' : 'c4',
                null,
                $mainnet ? 'bitcoincash' : 'bchtest',
            ),
            Coin::DASH => new self(
                $mainnet ? '4c' : '8c',
                $mainnet ? '10' : '13',
                null,
                null,
            ),
            Coin::ETH => throw new UnsupportedCoinException('Ethereum does not use UTXO address parameters.'),
            Coin::TRON => throw new UnsupportedCoinException('Tron does not use UTXO address parameters.'),
            Coin::XRP => throw new UnsupportedCoinException('XRP does not use UTXO address parameters.'),
        };
    }
}
