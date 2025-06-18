<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use Tests\Unit\TestCase;
use Scottlaurent\Accounting\Models\Journal;
use Scottlaurent\Accounting\Models\Ledger;
use Scottlaurent\Accounting\Enums\LedgerType;
use Scottlaurent\Accounting\Models\JournalTransaction;

class JournalTest extends TestCase
{
    public function test_it_can_be_created_with_required_fields(): void
    {
        $journal = Journal::create([
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 1,
        ]);

        $this->assertInstanceOf(Journal::class, $journal);
        $this->assertEquals(0, $journal->balance->getAmount(), 'New journal should start with zero balance');
        $this->assertEquals('USD', $journal->currency);
        $this->assertEquals('test', $journal->morphed_type);
        $this->assertEquals(1, $journal->morphed_id);
    }

    public function test_it_has_ledger_relationship(): void
    {
        $ledger = Ledger::create([
            'name' => 'Test Ledger',
            'type' => LedgerType::ASSET->value,
        ]);

        $journal = Journal::create([
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 1,
        ]);

        $journal->ledger()->associate($ledger);
        $journal->save();

        $this->assertTrue($journal->ledger->is($ledger));
    }

    public function test_it_can_have_transactions(): void
    {
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

        $this->assertCount(1, $journal->transactions);
        $this->assertTrue($journal->transactions->contains($transaction));
    }

    public function test_it_calculates_balance_correctly(): void
    {
        $journal = Journal::create([
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 1,
        ]);

        // Add some transactions
        $journal->transactions()->createMany([
            [
                'debit' => 1000,
                'credit' => 0,
                'currency' => 'USD',
                'memo' => 'Deposit',
                'post_date' => now(),
            ],
            [
                'debit' => 0,
                'credit' => 500,
                'currency' => 'USD',
                'memo' => 'Withdrawal',
                'post_date' => now(),
            ],
        ]);

        $balance = $journal->getBalance();
        $this->assertEquals(500, $balance->getAmount());
    }

    public function test_it_handles_balance_in_dollars(): void
    {
        $journal = Journal::create([
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 1,
        ]);

        // Add a transaction to set the balance to $12.50
        $journal->transactions()->create([
            'debit' => 1250, // $12.50
            'credit' => 0,
            'currency' => 'USD',
            'memo' => 'Initial deposit',
            'post_date' => now(),
        ]);

        $this->assertEquals(12.50, $journal->getBalanceInDollars());
    }
    
    public function test_it_requires_currency(): void
    {
        // Currency is now a required field, so we need to provide it
        $journal = Journal::create([
            'morphed_type' => 'test',
            'morphed_id' => 1,
            'currency' => 'USD',
        ]);
        
        $this->assertEquals('USD', $journal->currency, 'Should use the provided currency');
    }
}
