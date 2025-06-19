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
            'revenue' => 'Revenue',
            'expense' => 'Expense',
            'gain' => 'Gain',
            'loss' => 'Loss',
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

    public function test_it_calculates_balance_correctly_for_equity(): void
    {
        $ledger = Ledger::create([
            'name' => 'Equity Account',
            'type' => LedgerType::EQUITY->value,
        ]);

        $journal = $ledger->journals()->create([
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 1,
        ]);
        
        // Credit increases equity accounts
        $journal->transactions()->create([
            'debit' => 0,
            'credit' => 2000,
            'currency' => 'USD',
            'memo' => 'Equity credit',
            'post_date' => now(),
        ]);

        $balance = $ledger->getCurrentBalance('USD');
        $this->assertEquals(2000, $balance->getAmount());
    }

    public function test_it_calculates_balance_correctly_for_revenue(): void
    {
        $ledger = Ledger::create([
            'name' => 'Revenue Account',
            'type' => LedgerType::REVENUE->value,
        ]);

        $journal = $ledger->journals()->create([
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 1,
        ]);
        
        // Credit increases revenue accounts
        $journal->transactions()->create([
            'debit' => 0,
            'credit' => 3500,
            'currency' => 'USD',
            'memo' => 'Revenue credit',
            'post_date' => now(),
        ]);

        $balance = $ledger->getCurrentBalance('USD');
        $this->assertEquals(3500, $balance->getAmount());
    }
    
    public function test_it_calculates_balance_correctly_for_gain(): void
    {
        $ledger = Ledger::create([
            'name' => 'Gain Account',
            'type' => LedgerType::GAIN->value,
        ]);

        $journal = $ledger->journals()->create([
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 1,
        ]);
        
        // Credit increases gain accounts
        $journal->transactions()->create([
            'debit' => 0,
            'credit' => 1000,
            'currency' => 'USD',
            'memo' => 'Gain on sale of asset',
            'post_date' => now(),
        ]);

        $balance = $ledger->getCurrentBalance('USD');
        $this->assertEquals(1000, $balance->getAmount());
    }
    
    public function test_it_calculates_balance_correctly_for_loss(): void
    {
        $ledger = Ledger::create([
            'name' => 'Loss Account',
            'type' => LedgerType::LOSS->value,
        ]);

        $journal = $ledger->journals()->create([
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 1,
        ]);
        
        // Debit increases loss accounts
        $journal->transactions()->create([
            'debit' => 750,
            'credit' => 0,
            'currency' => 'USD',
            'memo' => 'Loss on sale of asset',
            'post_date' => now(),
        ]);

        $balance = $ledger->getCurrentBalance('USD');
        $this->assertEquals(750, $balance->getAmount());
    }

    public function test_it_calculates_balance_correctly_for_expense(): void
    {
        $ledger = Ledger::create([
            'name' => 'Expense Account',
            'type' => LedgerType::EXPENSE->value,
        ]);

        $journal = $ledger->journals()->create([
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 1,
        ]);
        
        // Debit increases expense accounts
        $journal->transactions()->create([
            'debit' => 2500,
            'credit' => 0,
            'currency' => 'USD',
            'memo' => 'Expense debit',
            'post_date' => now(),
        ]);

        $balance = $ledger->getCurrentBalance('USD');
        $this->assertEquals(2500, $balance->getAmount());
    }

    public function test_get_current_balance_with_empty_journals(): void
    {
        $ledger = Ledger::create([
            'name' => 'Empty Ledger',
            'type' => LedgerType::ASSET->value,
        ]);

        $balance = $ledger->getCurrentBalance('USD');
        $this->assertEquals(0, $balance->getAmount());
    }

    public function test_get_current_balance_in_dollars_with_mixed_transactions(): void
    {
        $ledger = Ledger::create([
            'name' => 'Mixed Transaction Ledger',
            'type' => LedgerType::ASSET->value,
        ]);

        $journal = $ledger->journals()->create([
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 1,
        ]);
        
        // Add multiple transactions
        $journal->transactions()->createMany([
            [
                'debit' => 5000, // $50.00
                'credit' => 0,
                'currency' => 'USD',
                'memo' => 'Debit transaction',
                'post_date' => now(),
            ],
            [
                'debit' => 0,
                'credit' => 1500, // $15.00
                'currency' => 'USD',
                'memo' => 'Credit transaction',
                'post_date' => now(),
            ],
        ]);

        // For assets: debit - credit = 5000 - 1500 = 3500 = $35.00
        $balance = $ledger->getCurrentBalanceInDollars();
        $this->assertEquals(35.00, $balance);
    }
}
