<?php

declare(strict_types=1);

namespace Ud5495\XPub;

enum Scheme: string
{
    case BIP44 = 'bip-44';
    case BIP49 = 'bip-49';
    case BIP84 = 'bip-84';
}
