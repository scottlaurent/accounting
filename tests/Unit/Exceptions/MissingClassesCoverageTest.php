<?php

declare(strict_types=1);

namespace Tests\Unit\Exceptions;

use Tests\Unit\TestCase;

class MissingClassesCoverageTest extends TestCase
{

    public function test_config_file_coverage(): void
    {
        // Test that the config file is loaded and accessible
        $config = include __DIR__ . '/../../../src/config/accounting.php';
        
        $this->assertIsArray($config);
        $this->assertArrayHasKey('base_currency', $config);
        $this->assertEquals('USD', $config['base_currency']);
    }
}
