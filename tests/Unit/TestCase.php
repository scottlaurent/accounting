<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    // Any unit test specific setup can go here
    protected function setUp(): void
    {
        parent::setUp();
        // Additional unit test specific setup
    }
}
