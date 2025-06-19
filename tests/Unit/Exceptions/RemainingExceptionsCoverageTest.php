<?php

declare(strict_types=1);

namespace Tests\Unit\Exceptions;

use Scottlaurent\Accounting\Exceptions\InvalidJournalEntryValue;
use Scottlaurent\Accounting\Exceptions\InvalidJournalMethod;
use Scottlaurent\Accounting\Exceptions\JournalAlreadyExists;
use Tests\Unit\TestCase;

class RemainingExceptionsCoverageTest extends TestCase
{
    public function test_invalid_journal_entry_value_exception_instantiation(): void
    {
        // Test direct instantiation of InvalidJournalEntryValue
        $exception = new InvalidJournalEntryValue();
        
        $this->assertInstanceOf(\Scottlaurent\Accounting\Exceptions\BaseException::class, $exception);
        $this->assertEquals('Journal transaction entries must be a positive value', $exception->getMessage());
    }
    
    public function test_invalid_journal_method_exception_instantiation(): void
    {
        // Test direct instantiation of InvalidJournalMethod
        $exception = new InvalidJournalMethod();
        
        $this->assertInstanceOf(\Scottlaurent\Accounting\Exceptions\BaseException::class, $exception);
        $this->assertEquals('Journal methods must be credit or debit', $exception->getMessage());
    }
    
    public function test_journal_already_exists_exception_instantiation(): void
    {
        // Test direct instantiation of JournalAlreadyExists
        $exception = new JournalAlreadyExists();
        
        $this->assertInstanceOf(\Scottlaurent\Accounting\Exceptions\BaseException::class, $exception);
        $this->assertEquals('Journal already exists.', $exception->getMessage());
    }
    
    public function test_all_exception_classes_exist(): void
    {
        // Ensure all exception classes can be instantiated
        $exceptions = [
            new InvalidJournalEntryValue(),
            new InvalidJournalMethod(),
            new JournalAlreadyExists(),
        ];
        
        foreach ($exceptions as $exception) {
            $this->assertInstanceOf(\Exception::class, $exception);
            $this->assertNotEmpty($exception->getMessage());
        }
    }
}
