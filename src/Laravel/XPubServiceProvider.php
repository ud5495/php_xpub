<?php

declare(strict_types=1);

namespace Ud5495\XPub\Laravel;

use Ud5495\XPub\XPubService;
use Illuminate\Support\ServiceProvider;

final class XPubServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(XPubService::class, static fn (): XPubService => XPubService::withDefaults());
        $this->app->alias(XPubService::class, 'xpub');
    }
}
