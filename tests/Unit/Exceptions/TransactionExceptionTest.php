<?php

declare(strict_types=1);

namespace Tests\Unit\Exceptions;

use Money\Currency;
use Money\Money;
use Scottlaurent\Accounting\Models\Journal;
use Scottlaurent\Accounting\Transaction;
use Tests\Unit\TestCase;

class TransactionExceptionTest extends TestCase
{
    public function test_commit_successful_transaction_flow(): void
    {
        // This test covers the successful commit flow to ensure all code paths are hit

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

        // This should succeed and return a UUID
        $result = $transaction->commit();

        $this->assertIsString($result);
        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $result);
    }

    public function test_commit_with_referenced_objects_coverage(): void
    {
        // This test ensures the referenced object code path is covered

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

        $money = new Money(1500, new Currency('USD'));
        $transaction->addTransaction($journal1, 'debit', $money, 'Test debit', $journal2);
        $transaction->addTransaction($journal2, 'credit', $money, 'Test credit', $journal1);

        // This should succeed and handle referenced objects
        $result = $transaction->commit();

        $this->assertIsString($result);

        // Verify the referenced objects were set
        $transactions = \Scottlaurent\Accounting\Models\JournalTransaction::where('transaction_group', $result)->get();
        $this->assertCount(2, $transactions);

        foreach ($transactions as $tx) {
            $this->assertNotNull($tx->ref_class);
            $this->assertNotNull($tx->ref_class_id);
        }
    }
}
