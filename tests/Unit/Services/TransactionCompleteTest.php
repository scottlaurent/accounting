<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Tests\Unit\TestCase;
use Scottlaurent\Accounting\Models\Journal;
use Scottlaurent\Accounting\Transaction;
use Scottlaurent\Accounting\Exceptions\DebitsAndCreditsDoNotEqual;
use Money\Money;
use Money\Currency;

class TransactionCompleteTest extends TestCase
{
    public function test_verify_credits_equal_debits_with_detailed_message(): void
    {
        $this->expectException(DebitsAndCreditsDoNotEqual::class);
        $this->expectExceptionMessage('In this transaction, credits == 2000 and debits == 1500');
        
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
        
        // Add unbalanced transactions with specific amounts
        $creditMoney = new Money(2000, new Currency('USD'));
        $debitMoney = new Money(1500, new Currency('USD'));
        
        $transaction->addTransaction($journal1, 'credit', $creditMoney, 'Credit');
        $transaction->addTransaction($journal2, 'debit', $debitMoney, 'Debit');
        
        // This should trigger verifyTransactionCreditsEqualDebits with specific amounts
        $transaction->commit();
    }

    public function test_multiple_transactions_verification(): void
    {
        $this->expectException(DebitsAndCreditsDoNotEqual::class);
        
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

        $journal3 = Journal::create([
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 3,
        ]);
        
        // Add multiple transactions that don't balance
        $transaction->addTransaction($journal1, 'credit', new Money(1000, new Currency('USD')), 'Credit 1');
        $transaction->addTransaction($journal2, 'credit', new Money(500, new Currency('USD')), 'Credit 2');
        $transaction->addTransaction($journal3, 'debit', new Money(1000, new Currency('USD')), 'Debit 1');
        
        // Credits = 1500, Debits = 1000 - should fail
        $transaction->commit();
    }

    public function test_empty_transaction_verification(): void
    {
        $transaction = Transaction::newDoubleEntryTransactionGroup();
        
        // Empty transactions should have credits=0 and debits=0
        // This should actually pass verification since 0 == 0
        $result = $transaction->commit();
        
        // Should return a valid UUID
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $result);
    }

    public function test_mixed_debit_credit_calculations(): void
    {
        $this->expectException(DebitsAndCreditsDoNotEqual::class);
        
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

        $journal3 = Journal::create([
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 3,
        ]);

        $journal4 = Journal::create([
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 4,
        ]);
        
        // Mix of debits and credits that don't balance
        $transaction->addTransaction($journal1, 'debit', new Money(800, new Currency('USD')), 'Debit 1');
        $transaction->addTransaction($journal2, 'debit', new Money(300, new Currency('USD')), 'Debit 2');
        $transaction->addTransaction($journal3, 'credit', new Money(600, new Currency('USD')), 'Credit 1');
        $transaction->addTransaction($journal4, 'credit', new Money(400, new Currency('USD')), 'Credit 2');
        
        // Debits = 1100, Credits = 1000 - should fail
        $transaction->commit();
    }
}