<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Tests\Unit\TestCase;
use Scottlaurent\Accounting\Models\Journal;
use Scottlaurent\Accounting\Transaction;
use Scottlaurent\Accounting\Exceptions\TransactionCouldNotBeProcessed;
use Money\Money;
use Money\Currency;
use Carbon\Carbon;

class TransactionAdditionalTest extends TestCase
{

    public function test_verify_transaction_credits_equal_debits_indirectly(): void
    {
        // This tests the private verifyTransactionCreditsEqualDebits method indirectly
        // by creating a transaction where credits != debits
        $this->expectException(\Scottlaurent\Accounting\Exceptions\DebitsAndCreditsDoNotEqual::class);
        
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
        
        // Add unbalanced transactions
        $money1 = new Money(1000, new Currency('USD'));
        $money2 = new Money(1500, new Currency('USD')); // Different amount!
        
        $transaction->addTransaction($journal1, 'debit', $money1, 'Test debit');
        $transaction->addTransaction($journal2, 'credit', $money2, 'Test credit');
        
        // This should call verifyTransactionCreditsEqualDebits and throw exception
        $transaction->commit();
    }

    public function test_commit_empty_transactions(): void
    {
        $this->expectException(\Scottlaurent\Accounting\Exceptions\DebitsAndCreditsDoNotEqual::class);
        
        $transaction = Transaction::newDoubleEntryTransactionGroup();
        
        // Empty transactions should have credits=0 and debits=0, which should pass verification
        // Let's add a single transaction to make it unbalanced
        $journal = Journal::create([
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 1,
        ]);
        
        $money = new Money(1000, new Currency('USD'));
        $transaction->addTransaction($journal, 'credit', $money, 'Unbalanced credit');
        
        // Now it's unbalanced (credit without matching debit)
        $transaction->commit();
    }

    public function test_add_transaction_with_all_parameters(): void
    {
        $transaction = Transaction::newDoubleEntryTransactionGroup();
        
        $journal = Journal::create([
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 1,
        ]);

        // Create a reference object
        $referenceJournal = Journal::create([
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 2,
        ]);
        
        $money = new Money(2000, new Currency('USD'));
        $postDate = Carbon::now()->subDays(1);
        
        // Test with all parameters including reference object and post date
        $transaction->addTransaction(
            $journal,
            'credit',
            $money,
            'Full parameter test',
            $referenceJournal,
            $postDate
        );
        
        $pending = $transaction->getTransactionsPending();
        
        $this->assertCount(1, $pending);
        $this->assertEquals('credit', $pending[0]['method']);
        $this->assertEquals(2000, $pending[0]['money']->getAmount());
        $this->assertEquals('Full parameter test', $pending[0]['memo']);
        $this->assertTrue($pending[0]['referencedObject']->is($referenceJournal));
        $this->assertEquals($postDate, $pending[0]['postdate']);
    }

    public function test_add_dollar_transaction_with_all_parameters(): void
    {
        $transaction = Transaction::newDoubleEntryTransactionGroup();

        $journal = Journal::create([
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 1,
        ]);

        $referenceJournal = Journal::create([
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 2,
        ]);

        $postDate = Carbon::now()->subDays(2);

        // Test addDollarTransaction with all parameters
        $transaction->addDollarTransaction(
            $journal,
            'debit',
            45.67,
            'Dollar transaction with all params',
            $referenceJournal,
            $postDate
        );

        $pending = $transaction->getTransactionsPending();

        $this->assertCount(1, $pending);
        $this->assertEquals('debit', $pending[0]['method']);
        $this->assertEquals(4567, $pending[0]['money']->getAmount()); // $45.67 = 4567 cents
        $this->assertEquals('Dollar transaction with all params', $pending[0]['memo']);
        $this->assertTrue($pending[0]['referencedObject']->is($referenceJournal));
        $this->assertEquals($postDate, $pending[0]['postdate']);
    }
}