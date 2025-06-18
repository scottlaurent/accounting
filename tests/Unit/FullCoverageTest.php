<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\Unit\TestCase;
use Scottlaurent\Accounting\Models\Journal;
use Scottlaurent\Accounting\Models\JournalTransaction;
use Scottlaurent\Accounting\Transaction;
use Scottlaurent\Accounting\Exceptions\TransactionCouldNotBeProcessed;
use Money\Money;
use Money\Currency;
use Illuminate\Support\Facades\DB;

class FullCoverageTest extends TestCase
{

    public function test_journal_boot_event_coverage(): void
    {
        // Test to ensure boot events are covered
        $journal = new Journal([
            'currency' => 'GBP',
            'morphed_type' => 'test',
            'morphed_id' => 999,
        ]);
        
        // The creating event should set balance
        $journal->save();
        
        $this->assertEquals(0, $journal->getAttributes()['balance']);
    }

    public function test_journal_transaction_deleted_event(): void
    {
        // Test the deleted event handler in JournalTransaction
        $journal = Journal::create([
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 1,
        ]);

        $transaction = $journal->transactions()->create([
            'debit' => 1000,
            'credit' => 0,
            'currency' => 'USD',
            'memo' => 'Test transaction',
            'post_date' => now(),
        ]);

        // Verify transaction exists
        $this->assertNotNull($transaction->id);
        
        // Delete should trigger the boot event
        $transaction->delete();
        
        // Test passes if no exception thrown
        $this->assertTrue(true);
    }

    public function test_all_edge_cases_in_journal(): void
    {
        $journal = Journal::create([
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 1,
        ]);
        
        // Test edge cases that might not be covered
        
        // 1. Test getCurrentBalance edge case
        $currentBalance = $journal->getCurrentBalance();
        $this->assertEquals(0, $currentBalance->getAmount());
        
        // 2. Test balance calculation with multiple currencies (should use journal currency)
        $journal->transactions()->create([
            'debit' => 1000,
            'credit' => 0,
            'currency' => 'EUR', // Different currency
            'memo' => 'Mixed currency',
            'post_date' => now(),
        ]);
        
        $balance = $journal->getBalance();
        // Should still calculate correctly
        $this->assertEquals(1000, $balance->getAmount());
    }

    public function test_complete_transaction_flow_coverage(): void
    {
        // Test a complete transaction flow to ensure all code paths are hit
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
        
        // Create a complex transaction with all features
        $money1 = new Money(1500, new Currency('USD'));
        $money2 = new Money(1500, new Currency('USD'));
        
        // Add transactions with all possible parameters
        $transaction->addTransaction(
            $journal1,
            'debit',
            $money1,
            'Complete test debit',
            $journal2, // reference object
            \Carbon\Carbon::now()->subHours(2)
        );
        
        $transaction->addTransaction(
            $journal2,
            'credit',
            $money2,
            'Complete test credit',
            $journal1, // reference object
            \Carbon\Carbon::now()->subHours(1)
        );
        
        // This should exercise all code paths in commit()
        $transactionId = $transaction->commit();
        
        $this->assertIsString($transactionId);
        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $transactionId);
        
        // Verify the transactions were created with references
        $createdTransactions = JournalTransaction::where('transaction_group', $transactionId)->get();
        $this->assertCount(2, $createdTransactions);
        
        // Check that references were set
        $debitTransaction = $createdTransactions->where('journal_id', $journal1->id)->first();
        $this->assertEquals($journal2::class, $debitTransaction->ref_class);
        $this->assertEquals($journal2->id, $debitTransaction->ref_class_id);
    }
}