<?php

declare(strict_types=1);

namespace Bedud5495\XPub;

enum Coin: string
{
    case BTC = 'btc';
    case ETH = 'eth';
    case TRON = 'tron';
    case LTC = 'ltc';
    case DOGE = 'doge';
    case BCH = 'bch';
    case DASH = 'dash';
}
