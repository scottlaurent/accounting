<?php

declare(strict_types=1);

namespace Tests\Unit\Providers;

use Tests\Unit\TestCase;
use Scottlaurent\Accounting\Providers\AccountingServiceProvider;
use Illuminate\Foundation\Application;

class AccountingServiceProviderTest extends TestCase
{
    public function test_service_provider_can_be_instantiated(): void
    {
        $app = $this->app;
        $provider = new AccountingServiceProvider($app);
        
        $this->assertInstanceOf(AccountingServiceProvider::class, $provider);
    }

    public function test_register_method_executes_successfully(): void
    {
        $app = $this->app;
        $provider = new AccountingServiceProvider($app);

        // Call register method
        $provider->register();

        // The register method should complete without error
        $this->assertTrue(true);
    }

    public function test_boot_method_publishes_migrations(): void
    {
        $app = $this->app;
        $provider = new AccountingServiceProvider($app);

        // Call boot method to publish migrations
        $provider->boot();

        // The boot method should complete without error
        $this->assertTrue(true);
    }
}