<?php

declare(strict_types=1);

namespace Ud5495\XPub\Address;

use Ud5495\XPub\Coin;
use Ud5495\XPub\Exception\UnsupportedCoinException;
use Ud5495\XPub\ExtendedKey;

final class AddressGeneratorRegistry
{
    /** @var list<AddressGenerator> */
    private readonly array $generators;

    /**
     * @param iterable<AddressGenerator> $generators
     */
    public function __construct(iterable $generators)
    {
        $list = [];

        foreach ($generators as $generator) {
            $list[] = $generator;
        }

        $this->generators = $list;
    }

    public function for(Coin $coin): AddressGenerator
    {
        foreach ($this->generators as $generator) {
            if ($generator->supports($coin)) {
                return $generator;
            }
        }

        throw new UnsupportedCoinException(sprintf('Coin "%s" is not supported.', $coin->value));
    }

    public function generate(ExtendedKey $key, Coin $coin): string
    {
        return $this->for($coin)->generate($key);
    }
}
