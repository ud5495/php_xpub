<?php

declare(strict_types=1);

namespace Ud5495\XPub;

enum Coin: string
{
    case BTC = 'btc';
    case ETH = 'eth';
    case TRON = 'tron';
    case XRP = 'xrp';
    case LTC = 'ltc';
    case DOGE = 'doge';
    case BCH = 'bch';
    case DASH = 'dash';
}
