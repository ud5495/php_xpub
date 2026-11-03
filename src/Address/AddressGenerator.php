<?php

declare(strict_types=1);

namespace Bedud5495\XPub\Address;

use Bedud5495\XPub\Coin;
use Bedud5495\XPub\ExtendedKey;

interface AddressGenerator
{
    public function supports(Coin $coin): bool;

    public function generate(ExtendedKey $key): string;
}
