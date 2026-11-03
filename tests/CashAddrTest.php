<?php

declare(strict_types=1);

namespace Ud5495\XPub\Tests;

use Ud5495\XPub\Codec\CashAddr;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CashAddrTest extends TestCase
{
    /**
     * Official vectors from the CashAddr specification.
     *
     * @see https://github.com/bitcoincashorg/bitcoincash.org/blob/master/spec/cashaddr.md
     *
     * @return iterable<string, array{string, int, string, string}>
     */
    public static function specVectors(): iterable
    {
        $hash = '76a04053bda0a88bda5177b86a15c3b29f559873';

        yield 'mainnet p2pkh' => [
            'bitcoincash',
            0,
            $hash,
            'bitcoincash:qpm2qsznhks23z7629mms6s4cwef74vcwvy22gdx6a',
        ];

        yield 'mainnet p2sh' => [
            'bitcoincash',
            1,
            $hash,
            'bitcoincash:ppm2qsznhks23z7629mms6s4cwef74vcwvn0h829pq',
        ];

        $payload = 'F5BF48B397DAE70BE82B3CCA4793F8EB2B6CDAC9';

        yield 'mainnet with fixed payload' => [
            'bitcoincash',
            0,
            $payload,
            'bitcoincash:qr6m7j9njldwwzlg9v7v53unlr4jkmx6eylep8ekg2',
        ];

        yield 'testnet with fixed payload' => [
            'bchtest',
            1,
            $payload,
            'bchtest:pr6m7j9njldwwzlg9v7v53unlr4jkmx6eyvwc0uz5t',
        ];

        yield 'custom prefix' => [
            'pref',
            1,
            $payload,
            'pref:pr6m7j9njldwwzlg9v7v53unlr4jkmx6ey65nvtks5',
        ];
    }

    #[DataProvider('specVectors')]
    public function testItMatchesTheCashAddrSpecificationVectors(
        string $prefix,
        int $type,
        string $hashHex,
        string $expected,
    ): void {
        $hash = hex2bin($hashHex);

        self::assertIsString($hash);
        self::assertSame($expected, (new CashAddr())->encode($prefix, $type, $hash));
    }
}
