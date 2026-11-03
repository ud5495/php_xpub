<?php

declare(strict_types=1);

namespace Bedud5495\XPub\Tests;

use Bedud5495\XPub\Coin;
use Bedud5495\XPub\Exception\InvalidDerivationPathException;
use Bedud5495\XPub\Exception\InvalidXPubException;
use Bedud5495\XPub\Scheme;
use Bedud5495\XPub\Version;
use Bedud5495\XPub\XPubService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class XPubServiceTest extends TestCase
{
    private XPubService $service;

    protected function setUp(): void
    {
        $this->service = XPubService::withDefaults();
    }

    #[DataProvider('roundTripVectors')]
    public function testItCanParseAndStringifyExtendedKeys(string $parent): void
    {
        $key = $this->service->fromString($parent);

        self::assertSame($parent, $this->service->toString($key));
    }

    #[DataProvider('roundTripVectors')]
    public function testItCanRoundTripHexSerialization(string $parent): void
    {
        $key = $this->service->fromString($parent);

        $hex = $this->service->toString($key, true);
        self::assertMatchesRegularExpression('/^[0-9a-f]{164}$/', $hex);

        $decoded = $this->service->fromHex($hex);
        self::assertSame($parent, $this->service->toString($decoded));
    }

    /**
     * @param int|array<int, int> $index
     */
    #[DataProvider('derivationVectors')]
    public function testItDerivesCorrectKeysAndAddresses(
        string $parent,
        ?Scheme $scheme,
        int|array $index,
        ?string $expectedChild,
        ?string $expectedAddress,
        Coin $coin,
    ): void {
        $key = $this->service->fromString($parent, $scheme);
        $child = $this->service->derive($key, $index);

        if ($expectedChild !== null) {
            self::assertSame($expectedChild, $this->service->toString($child));
        }

        if ($expectedAddress !== null) {
            self::assertSame($expectedAddress, $this->service->toAddress($child, $coin));
        }
    }

    #[DataProvider('invalidVectors')]
    public function testItRejectsInvalidExtendedKeys(string $invalid): void
    {
        $this->expectException(InvalidXPubException::class);

        $this->service->fromString($invalid);
    }

    public function testItConvertsBetweenVersions(): void
    {
        $key = $this->service->fromString(
            'xpub68Gmy5EdvgibQVfPdqkBBCHxA5htiqg55crXYuXoQRKfDBFA1WEjWgP6LHhwBZeNK1VTsfTFUHCdrfp1bgwQ9xv5ski8PX9rL2dZXvgGDnw'
        );

        $converted = $this->service->withVersion($key, Version::ZPUB);

        self::assertSame(
            'zpub6mwJaQaUE3oZ763dJZKRbNUxW1znc5f4uqty7hKaAS5RKNscWpZrkohNNhd7BNxD8Hj5NceNPbujdF3935mRkSHHcS6yZLnpsUkrK1XoMLr',
            $this->service->toString($converted)
        );
    }

    public function testItRejectsHardenedIndexes(): void
    {
        $key = $this->service->fromString(
            'xpub68Gmy5EdvgibQVfPdqkBBCHxA5htiqg55crXYuXoQRKfDBFA1WEjWgP6LHhwBZeNK1VTsfTFUHCdrfp1bgwQ9xv5ski8PX9rL2dZXvgGDnw'
        );

        $this->expectException(InvalidDerivationPathException::class);

        $this->service->derive($key, 0x80000000);
    }

    public function testItRejectsNegativeIndexes(): void
    {
        $key = $this->service->fromString(
            'xpub68Gmy5EdvgibQVfPdqkBBCHxA5htiqg55crXYuXoQRKfDBFA1WEjWgP6LHhwBZeNK1VTsfTFUHCdrfp1bgwQ9xv5ski8PX9rL2dZXvgGDnw'
        );

        $this->expectException(InvalidDerivationPathException::class);

        $this->service->derive($key, -1);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function roundTripVectors(): iterable
    {
        foreach (self::derivationVectors() as $name => $vector) {
            yield $name => [$vector[0]];
        }
    }

    /**
     * @return iterable<string, array{string, ?Scheme, int|array<int, int>, ?string, ?string, Coin}>
     */
    public static function derivationVectors(): iterable
    {
        yield 'bip44 child 1' => [
            'xpub68Gmy5EdvgibQVfPdqkBBCHxA5htiqg55crXYuXoQRKfDBFA1WEjWgP6LHhwBZeNK1VTsfTFUHCdrfp1bgwQ9xv5ski8PX9rL2dZXvgGDnw',
            null,
            1,
            'xpub6ASuArnXKPbfEwhqN6e3mwBcDTgzisQN1wXN9BJcM47sSikHjJf3UFHKkNAWbWMiGj7Wf5uMash7SyYq527Hqck2AxYysAA7xmALppuCkwQ',
            '1JQheacLPdM5ySCkrZkV66G2ApAXe1mqLj',
            Coin::BTC,
        ];

        yield 'bip44 child 2' => [
            'xpub6D4BDPcP2GT577Vvch3R8wDkScZWzQzMMUm3PWbmWvVJrZwQY4VUNgqFJPMM3No2dFDFGTsxxpG5uJh7n7epu4trkrX7x7DogT5Uv6fcLW5',
            null,
            2,
            'xpub6FHa3pjLCk84BayeJxFW2SP4XRrFd1JYnxeLeU8EqN3vDfZmbqBqaGJAyiLjTAwm6ZLRQUMv1ZACTj37sR62cfN7fe5JnJ7dh8zL4fiyLHV',
            '1LjmJcdPnDHhNTUgrWyhLGnRDKxQjoxAgt',
            Coin::BTC,
        ];

        yield 'bip44 child 1000000000' => [
            'xpub6FHa3pjLCk84BayeJxFW2SP4XRrFd1JYnxeLeU8EqN3vDfZmbqBqaGJAyiLjTAwm6ZLRQUMv1ZACTj37sR62cfN7fe5JnJ7dh8zL4fiyLHV',
            null,
            1000000000,
            'xpub6H1LXWLaKsWFhvm6RVpEL9P4KfRZSW7abD2ttkWP3SSQvnyA8FSVqNTEcYFgJS2UaFcxupHiYkro49S8yGasTvXEYBVPamhGW6cFJodrTHy',
            '1LZiqrop2HGR4qrH1ULZPyBpU6AUP49Uam',
            Coin::BTC,
        ];

        yield 'bip44 child 0' => [
            'xpub661MyMwAqRbcFW31YEwpkMuc5THy2PSt5bDMsktWQcFF8syAmRUapSCGu8ED9W6oDMSgv6Zz8idoc4a6mr8BDzTJY47LJhkJ8UB7WEGuduB',
            null,
            0,
            'xpub69H7F5d8KSRgmmdJg2KhpAK8SR3DjMwAdkxj3ZuxV27CprR9LgpeyGmXUbC6wb7ERfvrnKZjXoUmmDznezpbZb7ap6r1D3tgFxHmwMkQTPH',
            '19EuDJdgfRkwCmRzbzVBHZWQG9QNWhftbZ',
            Coin::BTC,
        ];

        yield 'bip44 child 1 (second chain)' => [
            'xpub6ASAVgeehLbnwdqV6UKMHVzgqAG8Gr6riv3Fxxpj8ksbH9ebxaEyBLZ85ySDhKiLDBrQSARLq1uNRts8RuJiHjaDMBU4Zn9h8LZNnBC5y4a',
            null,
            1,
            'xpub6DF8uhdarytz3FWdA8TvFSvvAh8dP3283MY7p2V4SeE2wyWmG5mg5EwVvmdMVCQcoNJxGoWaU9DCWh89LojfZ537wTfunKau47EL2dhHKon',
            '1BxrAr2pHpeBheusmd6fHDP2tSLAUa3qsW',
            Coin::BTC,
        ];

        yield 'bip44 child 2 (second chain)' => [
            'xpub6ERApfZwUNrhLCkDtcHTcxd75RbzS1ed54G1LkBUHQVHQKqhMkhgbmJbZRkrgZw4koxb5JaHWkY4ALHY2grBGRjaDMzQLcgJvLJuZZvRcEL',
            null,
            2,
            'xpub6FnCn6nSzZAw5Tw7cgR9bi15UV96gLZhjDstkXXxvCLsUXBGXPdSnLFbdpq8p9HmGsApME5hQTZ3emM2rnY5agb9rXpVGyy3bdW6EEgAtqt',
            '14UKfRV9ZPUp6ZC9PLhqbRtxdihW9em3xt',
            Coin::BTC,
        ];

        yield 'testnet path 0/0' => [
            'tpubD6NzVbkrYhZ4Ymi1bLuVeV7QxdAJaqjZgrmbKZkE4xyF7SVb5dXhB2KNevFFYB54fPueAfEXx9D88wCX6RaCwJqGUu3tKjGuD3oAPbhMVpG',
            null,
            [0, 0],
            null,
            'myD4h7qQZgvBzNhaipxk4cfQfhB8ymaXLi',
            Coin::BTC,
        ];

        yield 'testnet path 0/1' => [
            'tpubD6NzVbkrYhZ4Ymi1bLuVeV7QxdAJaqjZgrmbKZkE4xyF7SVb5dXhB2KNevFFYB54fPueAfEXx9D88wCX6RaCwJqGUu3tKjGuD3oAPbhMVpG',
            null,
            [0, 1],
            null,
            'n1tNSTgmSrGeS3XbiWrG4foY7eagVJKJgg',
            Coin::BTC,
        ];

        yield 'testnet path 0/2' => [
            'tpubD6NzVbkrYhZ4Ymi1bLuVeV7QxdAJaqjZgrmbKZkE4xyF7SVb5dXhB2KNevFFYB54fPueAfEXx9D88wCX6RaCwJqGUu3tKjGuD3oAPbhMVpG',
            null,
            [0, 2],
            null,
            'mjRRCz4wTLX8pyUrwwuQqYd2f6XZyDeW6e',
            Coin::BTC,
        ];

        yield 'testnet path 0/3' => [
            'tpubD6NzVbkrYhZ4Ymi1bLuVeV7QxdAJaqjZgrmbKZkE4xyF7SVb5dXhB2KNevFFYB54fPueAfEXx9D88wCX6RaCwJqGUu3tKjGuD3oAPbhMVpG',
            null,
            [0, 3],
            null,
            'mg6NRCZULcYUNdpd15ZUm6ipWARVF1QSzq',
            Coin::BTC,
        ];

        yield 'testnet path 0/4' => [
            'tpubD6NzVbkrYhZ4Ymi1bLuVeV7QxdAJaqjZgrmbKZkE4xyF7SVb5dXhB2KNevFFYB54fPueAfEXx9D88wCX6RaCwJqGUu3tKjGuD3oAPbhMVpG',
            null,
            [0, 4],
            null,
            'mx3dAxtAGzSYbYzVbRP5Y2Hi7W5KGKyhav',
            Coin::BTC,
        ];

        yield 'testnet path 0/5' => [
            'tpubD6NzVbkrYhZ4Ymi1bLuVeV7QxdAJaqjZgrmbKZkE4xyF7SVb5dXhB2KNevFFYB54fPueAfEXx9D88wCX6RaCwJqGUu3tKjGuD3oAPbhMVpG',
            null,
            [0, 5],
            null,
            'mvxzuevzQGHLuD37WW8AYafj15ePw7yxpL',
            Coin::BTC,
        ];

        yield 'eth path 0/0' => [
            'xpub6CC4r6jLdZSXWTMXjDC8zdQ9AhWowFf7bsyXNAaGbpSvVtwwXgZPuEhGqDURcCdTXBSNC3ZJv23FmhNGvES7nrNK5QfDDnRd8exT9usce2s',
            null,
            [0, 0],
            null,
            '0xB3a30E4631097BA14E8998aD256A36069fb091E3',
            Coin::ETH,
        ];

        yield 'eth path 0/1' => [
            'xpub6CC4r6jLdZSXWTMXjDC8zdQ9AhWowFf7bsyXNAaGbpSvVtwwXgZPuEhGqDURcCdTXBSNC3ZJv23FmhNGvES7nrNK5QfDDnRd8exT9usce2s',
            null,
            [0, 1],
            null,
            '0x3126c8ae7AF5D541f0e0b616fe692D95D343e9d7',
            Coin::ETH,
        ];

        yield 'eth path 0/2' => [
            'xpub6CC4r6jLdZSXWTMXjDC8zdQ9AhWowFf7bsyXNAaGbpSvVtwwXgZPuEhGqDURcCdTXBSNC3ZJv23FmhNGvES7nrNK5QfDDnRd8exT9usce2s',
            null,
            [0, 2],
            null,
            '0x17D26a6BA7a0099e5e6d031248410b397780C047',
            Coin::ETH,
        ];

        yield 'eth path 0/3' => [
            'xpub6CC4r6jLdZSXWTMXjDC8zdQ9AhWowFf7bsyXNAaGbpSvVtwwXgZPuEhGqDURcCdTXBSNC3ZJv23FmhNGvES7nrNK5QfDDnRd8exT9usce2s',
            null,
            [0, 3],
            null,
            '0xd338459525C6bd85489b677d55b12e7821e0172d',
            Coin::ETH,
        ];

        yield 'eth path 0/4' => [
            'xpub6CC4r6jLdZSXWTMXjDC8zdQ9AhWowFf7bsyXNAaGbpSvVtwwXgZPuEhGqDURcCdTXBSNC3ZJv23FmhNGvES7nrNK5QfDDnRd8exT9usce2s',
            null,
            [0, 4],
            null,
            '0x7f65930f50E2F29405454160756c6F98EF3B8E7a',
            Coin::ETH,
        ];

        yield 'eth path 0/5' => [
            'xpub6CC4r6jLdZSXWTMXjDC8zdQ9AhWowFf7bsyXNAaGbpSvVtwwXgZPuEhGqDURcCdTXBSNC3ZJv23FmhNGvES7nrNK5QfDDnRd8exT9usce2s',
            null,
            [0, 5],
            null,
            '0xe11f15608e043d0fF31105bFC9861079Fb3A67d4',
            Coin::ETH,
        ];

        yield 'bip44 path 0/0' => [
            'xpub6BosfCnifzxcFwrSzQiqu2DBVTshkCXacvNsWGYJVVhhawA7d4R5WSWGFNbi8Aw6ZRc1brxMyWMzG3DSSSSoekkudhUd9yLb6qx39T9nMdj',
            null,
            [0, 0],
            null,
            '1LqBGSKuX5yYUonjxT5qGfpUsXKYYWeabA',
            Coin::BTC,
        ];

        yield 'bip84 path 0/0' => [
            'zpub6rFR7y4Q2AijBEqTUquhVz398htDFrtymD9xYYfG1m4wAcvPhXNfE3EfH1r1ADqtfSdVCToUG868RvUUkgDKf31mGDtKsAYz2oz2AGutZYs',
            null,
            [0, 0],
            null,
            'bc1qcr8te4kr609gcawutmrza0j4xv80jy8z306fyu',
            Coin::BTC,
        ];

        yield 'bip84 path 0/1' => [
            'zpub6rFR7y4Q2AijBEqTUquhVz398htDFrtymD9xYYfG1m4wAcvPhXNfE3EfH1r1ADqtfSdVCToUG868RvUUkgDKf31mGDtKsAYz2oz2AGutZYs',
            null,
            [0, 1],
            null,
            'bc1qnjg0jd8228aq7egyzacy8cys3knf9xvrerkf9g',
            Coin::BTC,
        ];

        yield 'bip84 path 1/0' => [
            'zpub6rFR7y4Q2AijBEqTUquhVz398htDFrtymD9xYYfG1m4wAcvPhXNfE3EfH1r1ADqtfSdVCToUG868RvUUkgDKf31mGDtKsAYz2oz2AGutZYs',
            null,
            [1, 0],
            null,
            'bc1q8c6fshw2dlwun7ekn9qwf37cu2rn755upcp6el',
            Coin::BTC,
        ];

        yield 'bip84 path 0/19' => [
            'zpub6rFR7y4Q2AijBEqTUquhVz398htDFrtymD9xYYfG1m4wAcvPhXNfE3EfH1r1ADqtfSdVCToUG868RvUUkgDKf31mGDtKsAYz2oz2AGutZYs',
            null,
            [0, 19],
            null,
            'bc1q27yd7vz8m5kz230wuyncfe3pyazez6ah58yzy0',
            Coin::BTC,
        ];

        yield 'eth child 0' => [
            'xpub6BjPesomJQtThgmUr1hokZnbB3tpb73eQzjfD7HtZouvUVwuxJLPEwnk8UUzRncvQhgHLmndE8mTEpJDq2ekZ9mEeDYft82CNNZdGNTfnfQ',
            null,
            0,
            'xpub6EWegWNEAqbjvYW45eighKZnyhaSbc9Y1peQQsbnVPNnvuyZg2KRA64fBJmmK2jmJK86MECTC4EcUndqQ62dVe4xXzWYQ7makJ9uNx2nzik',
            null,
            Coin::ETH,
        ];

        yield 'eth path 0/0 (second)' => [
            'xpub6BjPesomJQtThgmUr1hokZnbB3tpb73eQzjfD7HtZouvUVwuxJLPEwnk8UUzRncvQhgHLmndE8mTEpJDq2ekZ9mEeDYft82CNNZdGNTfnfQ',
            null,
            [0, 0],
            null,
            '0x2a2AdEDBb3CD7BCC66557d0fBdE6f9099D388d0B',
            Coin::ETH,
        ];

        yield 'xpub parsed as bip84 (coinomi)' => [
            'xpub661MyMwKB68aRJYj1bbjsZ4STEmPTewJgzQ66qSu6iULGvMpMk1o7RD4iWARcFjPbUbCVU7P4XZcCCm2yXJx86MzV4j6PZRYxySuYiVw1nz',
            Scheme::BIP84,
            [0, 0],
            null,
            'bc1qg5xe9egkeyd3ca68rkyhx7j0cfqnngl5p8a5kj',
            Coin::BTC,
        ];

        yield 'upub bip49' => [
            'upub5EFU65HtV5TeiSHmZZm7FUffBGy8UKeqp7vw43jYbvZPpoVsgU93oac7Wk3u6moKegAEWtGNF8DehrnHtv21XXEMYRUocHqguyjknFHYfgY',
            null,
            [0, 0],
            null,
            '2Mww8dCYPUpKHofjgcXcBCEGmniw9CoaiD2',
            Coin::BTC,
        ];

        yield 'xpub parsed as bip49 (coinomi)' => [
            'xpub661MyMwKB68aRCnMw21BgrgiihMhh7YdYD2s9hWWEFrXpJaUeEzb5ZowLULwfU1GeyXYqCXixNiJ6x24ihByL5yr1nikgGjhJyzG4NGZrWM',
            Scheme::BIP49,
            [0, 0],
            null,
            '37NAZC6VajjVv52jFxKGEgS4RgEL8RNu1a',
            Coin::BTC,
        ];

        yield 'xpub bip44 (coinomi)' => [
            'xpub661MyMwKB68aRDo82aDsX57nTDqmP3fDyBddYCaEAGSxvkPbEVEaDCzmP7MoQ5E7o18B8MF86KFh8WuwvdFz7CcxpcC1567SYATFCDTB5Sy',
            null,
            [0, 0],
            null,
            '1JkaLb1JCgSSRivHzwKrhFakrBw23HDYbd',
            Coin::BTC,
        ];

        yield 'vpub bip84 testnet' => [
            'vpub5YcTiJKG9ofiKBLMfxvsK1A7S2diqo2WsoHhaE2cS5iAN7JAAXiZhwi61FS78uCXFsCfSbRsvgstc8LaemtrMogWuP6AYWiTpf9U7nR9DZi',
            null,
            [0, 0],
            null,
            'tb1qm0t6zqaa9dw4xxayf2s334pdpmmuc6w9kmnd0z',
            Coin::BTC,
        ];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidVectors(): iterable
    {
        yield 'corrupted xpub' => [
            'xpub68Gmy5EdvgibQVfPdqkBBCHxA5htiqg56crXYuXoQRKfDBFA1WEjWgP6LHhwBZeKK1VTsfTFUHCdrfp1bgwQ9xv5ski8PX9rL2dZXvgGDnw',
        ];

        yield 'corrupted tpub' => [
            'tpubD6NzVbkrYhZ4Ymi1bLuVeV7QxdAJaqjZgrmbKZkE4xyF7SVb5dXhB2KNevFFYB54fPueAfEXx9D88wCX6RaCwJqGUu3tKjGuD3oAPblMVpG',
        ];

        yield 'corrupted zpub' => [
            'zpub6rFR7y4Q2AijBEqTUquhVz398htDFrtymD9xYYfG1m4wAcvPhXNfE3EfH1r1ADqtfSdVCToUG868RvUUkgDKf31mGDtKsAYz2oz2AGutZYe',
        ];

        yield 'truncated xpub' => [
            'xpub6BjPesomJQtThgmUr1hokZnbB3tpb73eQzjfD7HtZouvUVwuxJLPEwnk8UUzRncvQhgHLmndE8mTEpJDq2ekZ9mEeDYft82CNNZdGNTfnf',
        ];

        yield 'short xpub' => [
            'xpub6BjPesomJQtThgmUr1hokZnbB3tpb73eQzjfD7HtZouvUVwuxJLPEwnk8UUzRncvQhgHLmndE8mTEpJDq2ekZ9mEeDYft82CNNZdG',
        ];

        yield 'wrong version' => [
            'ypub5EFU65HtV5TeiSHmZZm7FUffBGy8UKeqp7vw43jYbvZPpoVsgU93oac7Wk3u6moKegAEWtGNF8DehrnHtv21XXEMYRUocHqguyjknFHYfgY',
        ];
    }
}
