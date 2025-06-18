<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use Tests\Unit\TestCase;
use Scottlaurent\Accounting\Models\Ledger;
use Scottlaurent\Accounting\Enums\LedgerType;
use Scottlaurent\Accounting\Models\Journal;
use Scottlaurent\Accounting\Models\JournalTransaction;

class LedgerTest extends TestCase
{
    public function test_it_can_be_created_with_valid_attributes(): void
    {
        $ledger = Ledger::create([
            'name' => 'Test Ledger',
            'type' => LedgerType::ASSET->value,
        ]);

        $this->assertInstanceOf(Ledger::class, $ledger);
        $this->assertEquals('Test Ledger', $ledger->name);
        $this->assertEquals(LedgerType::ASSET, $ledger->type);
    }

    public function test_it_has_correct_type_options(): void
    {
        $expected = [
            'asset' => 'Asset',
            'liability' => 'Liability',
            'equity' => 'Equity',
            'income' => 'Income',
            'expense' => 'Expense',
        ];

        $this->assertEquals($expected, Ledger::getTypeOptions());
    }

    public function test_it_calculates_balance_correctly_for_assets(): void
    {
        $ledger = Ledger::create([
            'name' => 'Asset Account',
            'type' => LedgerType::ASSET->value,
        ]);

        // Add some test data to the ledger
        $journal = $ledger->journals()->create([
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 1,
        ]);
        
        // Add a transaction to set the balance
        $journal->transactions()->create([
            'debit' => 1000,
            'credit' => 0,
            'currency' => 'USD',
            'memo' => 'Initial deposit',
            'post_date' => now(),
        ]);

        $balance = $ledger->getCurrentBalance('USD');
        $this->assertEquals(1000, $balance->getAmount());
    }

    public function test_it_calculates_balance_correctly_for_liabilities(): void
    {
        $ledger = Ledger::create([
            'name' => 'Liability Account',
            'type' => LedgerType::LIABILITY->value,
        ]);

        $journal = $ledger->journals()->create([
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 1,
        ]);
        
        // Credit increases liability accounts
        $journal->transactions()->create([
            'debit' => 0,
            'credit' => 1500, // $15.00
            'currency' => 'USD',
            'memo' => 'Initial credit',
            'post_date' => now(),
        ]);

        $balance = $ledger->getCurrentBalance('USD');
        $this->assertEquals(1500, $balance->getAmount());
    }

    public function test_it_returns_correct_dollar_amount(): void
    {
        $ledger = Ledger::create([
            'name' => 'Test Ledger',
            'type' => LedgerType::ASSET->value,
        ]);

        $journal = $ledger->journals()->create([
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 1,
        ]);
        
        $journal->transactions()->create([
            'debit' => 1000, // $10.00
            'credit' => 0,
            'currency' => 'USD',
            'memo' => 'Test transaction',
            'post_date' => now(),
        ]);

        $this->assertEquals(10.0, $ledger->getCurrentBalanceInDollars());
    }

    public function test_it_has_journals_relationship(): void
    {
        $ledger = Ledger::create([
            'name' => 'Test Ledger',
            'type' => LedgerType::ASSET->value,
        ]);

        $journal = $ledger->journals()->create([
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 1,
        ]);
        
        $this->assertTrue($ledger->journals->contains($journal));
    }

    public function test_it_has_journal_transactions_relationship(): void
    {
        $ledger = Ledger::create([
            'name' => 'Test Ledger',
            'type' => LedgerType::ASSET->value,
        ]);

        $journal = $ledger->journals()->create([
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
        
        $this->assertTrue($ledger->journalTransactions->contains($transaction));
    }
}
