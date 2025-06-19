<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use Tests\Unit\TestCase;
use Scottlaurent\Accounting\Models\Journal;
use Scottlaurent\Accounting\Models\Ledger;
use Scottlaurent\Accounting\Enums\LedgerType;
use Carbon\Carbon;
use Money\Money;
use Money\Currency;

class JournalAdditionalTest extends TestCase
{
    public function test_credit_with_raw_amount(): void
    {
        $journal = Journal::create([
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 1,
        ]);
        
        // Test credit with integer amount
        $transaction = $journal->credit(1500, 'Raw amount credit');
        
        $this->assertInstanceOf(\Scottlaurent\Accounting\Models\JournalTransaction::class, $transaction);
        $this->assertEquals(1500, $transaction->credit);
    }

    public function test_debit_with_raw_amount(): void
    {
        $journal = Journal::create([
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 1,
        ]);
        
        // Test debit with integer amount
        $transaction = $journal->debit(2000, 'Raw amount debit');
        
        $this->assertInstanceOf(\Scottlaurent\Accounting\Models\JournalTransaction::class, $transaction);
        $this->assertEquals(2000, $transaction->debit);
    }

    public function test_credit_with_transaction_group(): void
    {
        $journal = Journal::create([
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 1,
        ]);
        
        $money = new Money(1800, new Currency('USD'));
        $transaction = $journal->credit($money, 'Group credit', Carbon::now(), 'test-group-123');
        
        $this->assertEquals('test-group-123', $transaction->transaction_group);
    }

    public function test_debit_with_transaction_group(): void
    {
        $journal = Journal::create([
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 1,
        ]);
        
        $money = new Money(2200, new Currency('USD'));
        $transaction = $journal->debit($money, 'Group debit', Carbon::now(), 'test-group-456');
        
        $this->assertEquals('test-group-456', $transaction->transaction_group);
    }

    public function test_credit_dollars_with_post_date(): void
    {
        $journal = Journal::create([
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 1,
        ]);
        
        $postDate = Carbon::now()->subDays(5);
        $transaction = $journal->creditDollars(25.99, 'Credit with date', $postDate);
        
        $this->assertEquals(2599, $transaction->credit);
        $this->assertEquals($postDate->format('Y-m-d H:i:s'), $transaction->post_date->format('Y-m-d H:i:s'));
    }

    public function test_debit_dollars_with_post_date(): void
    {
        $journal = Journal::create([
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 1,
        ]);
        
        $postDate = Carbon::now()->subDays(3);
        $transaction = $journal->debitDollars(15.75, 'Debit with date', $postDate);
        
        $this->assertEquals(1575, $transaction->debit);
        $this->assertEquals($postDate->format('Y-m-d H:i:s'), $transaction->post_date->format('Y-m-d H:i:s'));
    }

    public function test_get_balance_with_no_transactions(): void
    {
        $journal = Journal::create([
            'currency' => 'EUR',
            'morphed_type' => 'test',
            'morphed_id' => 1,
        ]);
        
        $balance = $journal->getBalance();
        
        $this->assertEquals(0, $balance->getAmount());
        $this->assertEquals('EUR', $balance->getCurrency()->getCode());
    }
}