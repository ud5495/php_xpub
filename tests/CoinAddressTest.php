<?php

declare(strict_types=1);

namespace Bedud5495\XPub\Tests;

use Bedud5495\XPub\Coin;
use Bedud5495\XPub\Exception\UnsupportedAddressSchemeException;
use Bedud5495\XPub\Scheme;
use Bedud5495\XPub\XPubService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CoinAddressTest extends TestCase
{
    private const MAINNET_XPUB = 'xpub68Gmy5EdvgibQVfPdqkBBCHxA5htiqg55crXYuXoQRKfDBFA1WEjWgP6LHhwBZeNK1VTsfTFUHCdrfp1bgwQ9xv5ski8PX9rL2dZXvgGDnw';
    private const TESTNET_TPUB = 'tpubD6NzVbkrYhZ4Ymi1bLuVeV7QxdAJaqjZgrmbKZkE4xyF7SVb5dXhB2KNevFFYB54fPueAfEXx9D88wCX6RaCwJqGUu3tKjGuD3oAPbhMVpG';
    private const TESTNET_VPUB = 'vpub5YcTiJKG9ofiKBLMfxvsK1A7S2diqo2WsoHhaE2cS5iAN7JAAXiZhwi61FS78uCXFsCfSbRsvgstc8LaemtrMogWuP6AYWiTpf9U7nR9DZi';
    private const TESTNET_UPUB = 'upub5EFU65HtV5TeiSHmZZm7FUffBGy8UKeqp7vw43jYbvZPpoVsgU93oac7Wk3u6moKegAEWtGNF8DehrnHtv21XXEMYRUocHqguyjknFHYfgY';
    private const MAINNET_ZPUB = 'zpub6rFR7y4Q2AijBEqTUquhVz398htDFrtymD9xYYfG1m4wAcvPhXNfE3EfH1r1ADqtfSdVCToUG868RvUUkgDKf31mGDtKsAYz2oz2AGutZYs';
    private const MAINNET_BIP49_XPUB = 'xpub661MyMwKB68aRCnMw21BgrgiihMhh7YdYD2s9hWWEFrXpJaUeEzb5ZowLULwfU1GeyXYqCXixNiJ6x24ihByL5yr1nikgGjhJyzG4NGZrWM';
    private const TRON_XPUB = 'xpub6Dw3psxyMo73ox5g6HHLfHsp8j4K66fJmWYngSL3EucDshiU9mjmsFACX3M1TetKpApAEwvv3jNLFMg4PGSeDKioNVrAZfaVm6yaYGsMap9';
    private const XRP_XPUB = 'xpub6Duw1vrZFioQHUpKTEKMYNRkmHAJHFnXk2KaferFUvDwzSXvSY2deXJVJT1eatsHamQW4Rp2Dm3Y4vGuPu7nLxqgSAixg7xUQ37kB1VUR97';

    private XPubService $service;

    protected function setUp(): void
    {
        $this->service = XPubService::withDefaults();
    }

    /**
     * @param int|array<int, int> $path
     */
    #[DataProvider('addressVectors')]
    public function testItGeneratesAddressesForSupportedCoins(
        string $parent,
        ?Scheme $scheme,
        int|array $path,
        Coin $coin,
        string $expected,
    ): void {
        $key = $this->service->fromString($parent, $scheme);
        $child = $this->service->derive($key, $path);

        self::assertSame($expected, $this->service->toAddress($child, $coin));
    }

    public function testItSupportsEveryCoin(): void
    {
        $key = $this->service->fromString(self::MAINNET_XPUB);
        $child = $this->service->derive($key, 1);

        foreach (Coin::cases() as $coin) {
            $address = $this->service->toAddress($child, $coin);

            self::assertNotSame('', $address, sprintf('Coin "%s" produced an empty address.', $coin->value));
        }
    }

    #[DataProvider('unsupportedSchemeVectors')]
    public function testItRejectsSegwitSchemesForCoinsWithoutSegwit(
        string $parent,
        Scheme $scheme,
        Coin $coin,
    ): void {
        $key = $this->service->fromString($parent, $scheme);
        $child = $this->service->derive($key, [0, 0]);

        $this->expectException(UnsupportedAddressSchemeException::class);

        $this->service->toAddress($child, $coin);
    }

    /**
     * @return iterable<string, array{string, ?Scheme, int|array<int, int>, Coin, string}>
     */
    public static function addressVectors(): iterable
    {
        yield 'btc mainnet p2pkh' => [
            self::MAINNET_XPUB,
            null,
            1,
            Coin::BTC,
            '1JQheacLPdM5ySCkrZkV66G2ApAXe1mqLj',
        ];

        yield 'eth mainnet' => [
            self::MAINNET_XPUB,
            null,
            1,
            Coin::ETH,
            '0x29379f45F515C494483298225d1B347F73D1babF',
        ];

        yield 'tron mainnet' => [
            self::MAINNET_XPUB,
            null,
            1,
            Coin::TRON,
            'TDj9LctTLkSqiXQKELL4po3PgpnHKSHrJp',
        ];

        yield 'xrp mainnet' => [
            self::MAINNET_XPUB,
            null,
            1,
            Coin::XRP,
            'rJQ6e2cLPdMnySUkiZkVaaGpwFwXermqLj',
        ];

        yield 'ltc mainnet p2pkh' => [
            self::MAINNET_XPUB,
            null,
            1,
            Coin::LTC,
            'LcdeunvAUHb9EEtv2hjnN7KnP2Xoj12SsS',
        ];

        yield 'doge mainnet p2pkh' => [
            self::MAINNET_XPUB,
            null,
            1,
            Coin::DOGE,
            'DNYoBqYyh3FNWSPMb9k3drRd3wtpwEwrV2',
        ];

        yield 'dash mainnet p2pkh' => [
            self::MAINNET_XPUB,
            null,
            1,
            Coin::DASH,
            'Xt6YUqGEMLZg8NoLiT4hwcwp19kDfe7rWW',
        ];

        yield 'bch mainnet cashaddr' => [
            self::MAINNET_XPUB,
            null,
            1,
            Coin::BCH,
            'bitcoincash:qzl0tghe544ff243y3vlw2kee7x0r8rmhcd0wd5lgy',
        ];

        yield 'ltc testnet p2pkh' => [
            self::TESTNET_TPUB,
            null,
            [0, 0],
            Coin::LTC,
            'myD4h7qQZgvBzNhaipxk4cfQfhB8ymaXLi',
        ];

        yield 'doge testnet p2pkh' => [
            self::TESTNET_TPUB,
            null,
            [0, 0],
            Coin::DOGE,
            'nmtGfLRyz3qwdEykmfdP2sCyvhh2Siejzs',
        ];

        yield 'dash testnet p2pkh' => [
            self::TESTNET_TPUB,
            null,
            [0, 0],
            Coin::DASH,
            'ye1ZFGUm9vMbhwk6Rzcz8FZDvKeVeEVLua',
        ];

        yield 'bch testnet cashaddr' => [
            self::TESTNET_TPUB,
            null,
            [0, 0],
            Coin::BCH,
            'bchtest:qrppq8s8geq5hjpe5agh78xsesgr0dklgy2nvgx3xh',
        ];

        yield 'tron address 0' => [
            self::TRON_XPUB,
            null,
            0,
            Coin::TRON,
            'TFAnkgRx4wBdJLLxgcRwVvF4vkVGsxQnhi',
        ];

        yield 'tron address 1' => [
            self::TRON_XPUB,
            null,
            1,
            Coin::TRON,
            'TV5nWufu5Z1JrCb7byD8eLrp76vo3ZXy3w',
        ];

        yield 'tron address 2' => [
            self::TRON_XPUB,
            null,
            2,
            Coin::TRON,
            'TL8qet2hp9NTfuAuMeESNzWZaMWBUX2H3g',
        ];

        yield 'tron address 3' => [
            self::TRON_XPUB,
            null,
            3,
            Coin::TRON,
            'TVweoyBzm9xQabTecQKEmUyecaK5Li2ULX',
        ];

        yield 'tron address 4' => [
            self::TRON_XPUB,
            null,
            4,
            Coin::TRON,
            'TTj1Au1wjBkmhSQ4EFYZEjHgWjVij6yxtb',
        ];

        yield 'xrp address 0' => [
            self::XRP_XPUB,
            null,
            0,
            Coin::XRP,
            'rN7LCRMygHWWUEnDATAFrLCcGVQMgVm3gW',
        ];

        yield 'xrp address 1' => [
            self::XRP_XPUB,
            null,
            1,
            Coin::XRP,
            'rwZJsg29pRw7H1925QmKau52RKLoimfDdc',
        ];

        yield 'xrp address 2' => [
            self::XRP_XPUB,
            null,
            2,
            Coin::XRP,
            'r4XJwv1LDpYrJQ3t7Gv9BuZBFHa8JmnehG',
        ];

        yield 'xrp address 3' => [
            self::XRP_XPUB,
            null,
            3,
            Coin::XRP,
            'rUBZzPTwTgEwyVJqdjDCMCCuW1L1ENsB4U',
        ];

        yield 'xrp address 4' => [
            self::XRP_XPUB,
            null,
            4,
            Coin::XRP,
            'rHRHuGz56dNpnmGAKyUJ6TSHVDrrUstZ1r',
        ];

        yield 'xrp address 5' => [
            self::XRP_XPUB,
            null,
            5,
            Coin::XRP,
            'rBnJLuwKFrJt3K96L2XjAfhruoBCig1qP3',
        ];

        yield 'xrp address 6' => [
            self::XRP_XPUB,
            null,
            6,
            Coin::XRP,
            'rEw13ytcsDnqJ3G69GeCbYdn9m9A7gct2h',
        ];

        yield 'xrp address 7' => [
            self::XRP_XPUB,
            null,
            7,
            Coin::XRP,
            'r41bEmvgNskVFKp8PUUvTB9kjEKnowmsmB',
        ];

        yield 'xrp address 8' => [
            self::XRP_XPUB,
            null,
            8,
            Coin::XRP,
            'rN2jUKrFHQKUo4RP5ya3yFgwTvVYxReb6U',
        ];

        yield 'xrp address 9' => [
            self::XRP_XPUB,
            null,
            9,
            Coin::XRP,
            'rmDSHBgbAS3N8ygUoVMYfDy3g2Nq7Uwnb',
        ];

        yield 'ltc mainnet native segwit' => [
            self::MAINNET_ZPUB,
            null,
            [0, 0],
            Coin::LTC,
            'ltc1qcr8te4kr609gcawutmrza0j4xv80jy8z4nqduv',
        ];

        yield 'ltc testnet native segwit' => [
            self::TESTNET_VPUB,
            null,
            [0, 0],
            Coin::LTC,
            'tltc1qm0t6zqaa9dw4xxayf2s334pdpmmuc6w90n3nlt',
        ];

        yield 'ltc mainnet nested segwit' => [
            self::MAINNET_BIP49_XPUB,
            Scheme::BIP49,
            [0, 0],
            Coin::LTC,
            'MDaJs5WTXraviaJdMqJc4KgTkNpnC5kJrp',
        ];

        yield 'ltc testnet nested segwit' => [
            self::TESTNET_UPUB,
            null,
            [0, 0],
            Coin::LTC,
            'QRHtkDQdVvNNwrVjEdeCGviCw7Ny3SNNiA',
        ];
    }

    /**
     * @return iterable<string, array{string, Scheme, Coin}>
     */
    public static function unsupportedSchemeVectors(): iterable
    {
        foreach ([Coin::DOGE, Coin::BCH, Coin::DASH] as $coin) {
            yield $coin->value . ' bip49' => [
                self::MAINNET_BIP49_XPUB,
                Scheme::BIP49,
                $coin,
            ];

            yield $coin->value . ' bip84' => [
                self::MAINNET_ZPUB,
                Scheme::BIP84,
                $coin,
            ];
        }
    }
}
