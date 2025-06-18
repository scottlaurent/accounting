<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Carbon\Carbon;
use Money\Money;
use Money\Currency;
use Ramsey\Uuid\Uuid;
use Scottlaurent\Accounting\Models\Journal;
use Scottlaurent\Accounting\Transaction;
use Scottlaurent\Accounting\Exceptions\InvalidJournalEntryValue;
use Scottlaurent\Accounting\Exceptions\InvalidJournalMethod;
use Scottlaurent\Accounting\Exceptions\DebitsAndCreditsDoNotEqual;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;

class TransactionTest extends TestCase
{
    public function testNewDoubleEntryTransactionGroup()
    {
        $transaction = Transaction::newDoubleEntryTransactionGroup();
        $this->assertInstanceOf(Transaction::class, $transaction);
        $this->assertEmpty($transaction->getTransactionsPending());
    }

    public function testAddTransactionWithCredit()
    {
        $transaction = Transaction::newDoubleEntryTransactionGroup();
        $journal = Journal::create([
            'ledger_id' => 1,
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 1,
        ]);

        $money = new Money(1000, new Currency('USD'));

        $transaction->addTransaction($journal, 'credit', $money, 'Test credit');

        $transactions = $transaction->getTransactionsPending();
        $this->assertCount(1, $transactions);
        $this->assertEquals('credit', $transactions[0]['method']);
        $this->assertEquals(1000, $transactions[0]['money']->getAmount());
        $this->assertEquals('Test credit', $transactions[0]['memo']);
    }

    public function testAddTransactionWithDebit()
    {
        $transaction = Transaction::newDoubleEntryTransactionGroup();
        $journal = Journal::create([
            'ledger_id' => 1,
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 2,
        ]);

        $money = new Money(1500, new Currency('USD'));

        $transaction->addTransaction($journal, 'debit', $money, 'Test debit');

        $transactions = $transaction->getTransactionsPending();
        $this->assertCount(1, $transactions);
        $this->assertEquals('debit', $transactions[0]['method']);
        $this->assertEquals(1500, $transactions[0]['money']->getAmount());
    }

    public function testAddTransactionWithInvalidMethod()
    {
        $this->expectException(InvalidJournalMethod::class);
        
        $transaction = Transaction::newDoubleEntryTransactionGroup();
        $journal = Journal::create([
            'ledger_id' => 1,
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 3,
        ]);
        
        $money = new Money(1000, new Currency('USD'));
        $transaction->addTransaction($journal, 'invalid_method', $money);
    }

    public function testAddTransactionWithZeroAmount()
    {
        $this->expectException(InvalidJournalEntryValue::class);
        
        $transaction = Transaction::newDoubleEntryTransactionGroup();
        $journal = Journal::create([
            'ledger_id' => 1,
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 4,
        ]);
        
        $money = new Money(0, new Currency('USD'));
        $transaction->addTransaction($journal, 'credit', $money);
    }

    public function testAddDollarTransaction()
    {
        $transaction = Transaction::newDoubleEntryTransactionGroup();
        $journal = Journal::create([
            'ledger_id' => 1,
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 5,
        ]);
        
        $transaction->addDollarTransaction($journal, 'credit', 10.50, 'Test dollar transaction');
        
        $transactions = $transaction->getTransactionsPending();
        $this->assertCount(1, $transactions);
        $this->assertEquals(1050, $transactions[0]['money']->getAmount()); // $10.50 should be 1050 cents
    }

    public function testCommitWithUnbalancedTransactions()
    {
        $this->expectException(DebitsAndCreditsDoNotEqual::class);
        
        $transaction = Transaction::newDoubleEntryTransactionGroup();
        $journal = Journal::create([
            'ledger_id' => 1,
            'balance' => 0,
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 6,
        ]);
        
        $money = new Money(1000, new Currency('USD'));
        $transaction->addTransaction($journal, 'credit', $money);
        
        // Only a credit, no matching debit
        $transaction->commit();
    }

    public function testCommitWithBalancedTransactions()
    {
        $transaction = Transaction::newDoubleEntryTransactionGroup();
        
        // Create two journals
        $journal1 = Journal::create([
            'ledger_id' => 1,
            'balance' => 0,
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 7,
        ]);

        $journal2 = Journal::create([
            'ledger_id' => 2,
            'balance' => 0,
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 8,
        ]);
        
        $money = new Money(1000, new Currency('USD'));
        $transaction->addTransaction($journal1, 'debit', $money, 'Test debit');
        $transaction->addTransaction($journal2, 'credit', $money, 'Test credit');
        
        $transactionGroupId = $transaction->commit();
        
        // Verify transaction group ID is a valid UUID
        $this->assertTrue(Uuid::isValid($transactionGroupId));
        
        // Refresh journals to get updated balances
        $journal1->refresh();
        $journal2->refresh();
        
        // Verify journal balances were updated
        // In this implementation, debits decrease the balance and credits increase it
        // This is because getBalance() calculates as sum('debit') - sum('credit')
        $this->assertEquals(-1000, $journal1->balance->getAmount(), 'Debit should decrease balance');
        $this->assertEquals(1000, $journal2->balance->getAmount(), 'Credit should increase balance');
    }
}
