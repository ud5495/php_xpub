<?php

declare(strict_types=1);

namespace Ud5495\XPub\Address;

use Ud5495\XPub\Coin;
use Ud5495\XPub\ExtendedKey;

interface AddressGenerator
{
    public function supports(Coin $coin): bool;

    public function generate(ExtendedKey $key): string;
}
