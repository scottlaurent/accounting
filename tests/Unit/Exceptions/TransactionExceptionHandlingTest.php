<?php

declare(strict_types=1);

namespace Tests\Unit\Exceptions;

use Illuminate\Support\Facades\DB;
use Money\Currency;
use Money\Money;
use Scottlaurent\Accounting\Exceptions\TransactionCouldNotBeProcessed;
use Scottlaurent\Accounting\Models\Journal;
use Scottlaurent\Accounting\Transaction;
use Tests\Unit\TestCase;

class TransactionExceptionHandlingTest extends TestCase
{
    public function test_commit_exception_handling_with_database_failure(): void
    {
        $this->expectException(TransactionCouldNotBeProcessed::class);
        $this->expectExceptionMessage('Rolling Back Database. Message:');
        
        $transaction = Transaction::newDoubleEntryTransactionGroup();
        
        $journal1 = Journal::create([
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 1,
        ]);

        $journal2 = Journal::create([
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 2,
        ]);
        
        $money = new Money(1000, new Currency('USD'));
        $transaction->addTransaction($journal1, 'debit', $money, 'Test debit');
        $transaction->addTransaction($journal2, 'credit', $money, 'Test credit');
        
        // Create a scenario that will cause a database exception
        // by trying to insert into a non-existent table
        DB::statement('DROP TABLE IF EXISTS temp_accounting_journals');
        DB::statement('ALTER TABLE accounting_journals RENAME TO temp_accounting_journals');
        
        try {
            $transaction->commit();
        } finally {
            // Restore the table for other tests
            DB::statement('ALTER TABLE temp_accounting_journals RENAME TO accounting_journals');
        }
    }
    
    public function test_verify_transaction_credits_equal_debits_method(): void
    {
        // This test covers the private verifyTransactionCreditsEqualDebits method
        $transaction = Transaction::newDoubleEntryTransactionGroup();
        
        $journal1 = Journal::create([
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 3,
        ]);

        $journal2 = Journal::create([
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 4,
        ]);
        
        // Add unbalanced transactions to trigger the verification
        $money1 = new Money(1000, new Currency('USD'));
        $money2 = new Money(1500, new Currency('USD')); // Different amount
        
        $transaction->addTransaction($journal1, 'debit', $money1, 'Test debit');
        $transaction->addTransaction($journal2, 'credit', $money2, 'Test credit');
        
        $this->expectException(\Scottlaurent\Accounting\Exceptions\DebitsAndCreditsDoNotEqual::class);
        $this->expectExceptionMessage('In this transaction, credits == 1500 and debits == 1000');
        
        $transaction->commit();
    }
    
    public function test_transaction_with_multiple_currencies(): void
    {
        // Test transaction handling with different currencies
        $transaction = Transaction::newDoubleEntryTransactionGroup();
        
        $journal1 = Journal::create([
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 5,
        ]);

        $journal2 = Journal::create([
            'currency' => 'EUR',
            'morphed_type' => 'test',
            'morphed_id' => 6,
        ]);
        
        $usdMoney = new Money(1000, new Currency('USD'));
        $eurMoney = new Money(1000, new Currency('EUR')); // Same amount, different currency
        
        $transaction->addTransaction($journal1, 'debit', $usdMoney, 'USD debit');
        $transaction->addTransaction($journal2, 'credit', $eurMoney, 'EUR credit');
        
        // This should succeed as the amounts are equal even with different currencies
        $result = $transaction->commit();
        
        $this->assertIsString($result);
        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $result);
    }
}
