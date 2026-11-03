<?php

declare(strict_types=1);

namespace Bedud5495\XPub\Laravel;

use Bedud5495\XPub\XPubService;
use Illuminate\Support\ServiceProvider;

final class XPubServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(XPubService::class, static fn (): XPubService => XPubService::withDefaults());
        $this->app->alias(XPubService::class, 'xpub');
    }
}
