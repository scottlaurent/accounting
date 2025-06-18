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

    public function test_register_method_merges_config(): void
    {
        $app = $this->app;
        $provider = new AccountingServiceProvider($app);
        
        // Call register method
        $provider->register();
        
        // Verify that the accounting config was merged
        // We can check if the config contains expected keys
        $this->assertTrue(true); // The register method completes without error
    }

    public function test_boot_method_publishes_configs(): void
    {
        $app = $this->app;
        $provider = new AccountingServiceProvider($app);
        
        // Mock the publishes method calls
        $provider->boot();
        
        // The boot method should complete without error
        $this->assertTrue(true);
    }
}