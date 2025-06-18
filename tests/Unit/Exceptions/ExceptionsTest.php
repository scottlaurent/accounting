<?php

declare(strict_types=1);

namespace Tests\Unit\Exceptions;

use Tests\Unit\TestCase;
use Scottlaurent\Accounting\Exceptions\BaseException;
use Scottlaurent\Accounting\Exceptions\DebitsAndCreditsDoNotEqual;
use Scottlaurent\Accounting\Exceptions\InvalidJournalEntryValue;
use Scottlaurent\Accounting\Exceptions\InvalidJournalMethod;
use Scottlaurent\Accounting\Exceptions\JournalAlreadyExists;
use Scottlaurent\Accounting\Exceptions\TransactionCouldNotBeProcessed;

class ExceptionsTest extends TestCase
{
    public function test_missing_exception_classes_coverage(): void
    {
        // Test the remaining exception classes that might not be covered
        $invalidEntryException = new InvalidJournalEntryValue();
        $invalidMethodException = new InvalidJournalMethod();

        $this->assertInstanceOf(\Exception::class, $invalidEntryException);
        $this->assertInstanceOf(\Exception::class, $invalidMethodException);

        // Test with custom messages
        $customEntryException = new InvalidJournalEntryValue('Custom entry message');
        $customMethodException = new InvalidJournalMethod('Custom method message');

        $this->assertEquals('Custom entry message', $customEntryException->getMessage());
        $this->assertEquals('Custom method message', $customMethodException->getMessage());
    }

    public function test_base_exception_with_custom_message(): void
    {
        $exception = new BaseException('Custom error message');
        
        $this->assertEquals('Custom error message', $exception->getMessage());
        $this->assertInstanceOf(\Exception::class, $exception);
    }

    public function test_base_exception_with_default_message(): void
    {
        $exception = new BaseException();
        
        $this->assertEquals('', $exception->getMessage());
    }

    public function test_debits_and_credits_do_not_equal_exception(): void
    {
        $exception = new DebitsAndCreditsDoNotEqual('1000');
        
        $expectedMessage = 'Double Entry requires that debits equal credits.1000';
        $this->assertEquals($expectedMessage, $exception->getMessage());
        $this->assertInstanceOf(BaseException::class, $exception);
    }

    public function test_invalid_journal_entry_value_exception(): void
    {
        $exception = new InvalidJournalEntryValue();
        
        $this->assertEquals('Journal transaction entries must be a positive value', $exception->getMessage());
        $this->assertInstanceOf(BaseException::class, $exception);
    }

    public function test_invalid_journal_method_exception(): void
    {
        $exception = new InvalidJournalMethod();
        
        $this->assertEquals('Journal methods must be credit or debit', $exception->getMessage());
        $this->assertInstanceOf(BaseException::class, $exception);
    }

    public function test_journal_already_exists_exception(): void
    {
        $exception = new JournalAlreadyExists();
        
        $this->assertEquals('Journal already exists.', $exception->getMessage());
        $this->assertInstanceOf(BaseException::class, $exception);
    }

    public function test_journal_already_exists_exception_with_custom_message(): void
    {
        $exception = new JournalAlreadyExists('Custom journal exists message');
        
        $this->assertEquals('Custom journal exists message', $exception->getMessage());
        $this->assertInstanceOf(BaseException::class, $exception);
    }

    public function test_invalid_journal_entry_value_with_custom_message(): void
    {
        $exception = new InvalidJournalEntryValue('Custom entry value message');
        
        $this->assertEquals('Custom entry value message', $exception->getMessage());
        $this->assertInstanceOf(BaseException::class, $exception);
    }

    public function test_invalid_journal_method_with_custom_message(): void
    {
        $exception = new InvalidJournalMethod('Custom method message');
        
        $this->assertEquals('Custom method message', $exception->getMessage());
        $this->assertInstanceOf(BaseException::class, $exception);
    }

    public function test_transaction_could_not_be_processed_exception(): void
    {
        $exception = new TransactionCouldNotBeProcessed('Database error');
        
        $expectedMessage = 'Double Entry Transaction could not be processed. Database error';
        $this->assertEquals($expectedMessage, $exception->getMessage());
        $this->assertInstanceOf(BaseException::class, $exception);
    }

    public function test_invalid_journal_entry_value_exception_coverage(): void
    {
        // Test the exception class to ensure it's covered
        $exception = new InvalidJournalEntryValue();

        $this->assertEquals('Journal transaction entries must be a positive value', $exception->getMessage());
        $this->assertInstanceOf(\Scottlaurent\Accounting\Exceptions\BaseException::class, $exception);
    }

    public function test_invalid_journal_method_exception_coverage(): void
    {
        // Test the exception class to ensure it's covered
        $exception = new InvalidJournalMethod();

        $this->assertEquals('Journal methods must be credit or debit', $exception->getMessage());
        $this->assertInstanceOf(\Scottlaurent\Accounting\Exceptions\BaseException::class, $exception);
    }

    public function test_journal_already_exists_exception_coverage(): void
    {
        // Test the exception class to ensure it's covered
        $exception = new JournalAlreadyExists();

        $this->assertEquals('Journal already exists.', $exception->getMessage());
        $this->assertInstanceOf(\Scottlaurent\Accounting\Exceptions\BaseException::class, $exception);
    }

}