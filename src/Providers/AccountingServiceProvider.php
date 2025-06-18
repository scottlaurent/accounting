<?php

declare(strict_types=1);

namespace Scottlaurent\Accounting\Providers;

use Illuminate\Support\ServiceProvider;

class AccountingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../migrations/' => database_path('/migrations')
        ], 'migrations');
    }
}
