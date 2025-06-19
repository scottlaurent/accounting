<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use Tests\Unit\TestCase;
use Scottlaurent\Accounting\Models\Journal;
use Scottlaurent\Accounting\Models\Ledger;
use Scottlaurent\Accounting\Enums\LedgerType;

class JournalBootTest extends TestCase
{
    public function test_boot_creating_event_with_currency(): void
    {
        // Create a journal with currency - should initialize with zero balance
        $journal = new Journal([
            'currency' => 'EUR',
            'morphed_type' => 'test',
            'morphed_id' => 1,
        ]);
        
        // Before save, balance should not be set
        $this->assertNull($journal->getAttributes()['balance'] ?? null);
        
        $journal->save();
        
        // After save, the creating event should have set balance to 0
        $this->assertEquals(0, $journal->getAttributes()['balance']);
    }


    public function test_boot_created_event_with_currency(): void
    {
        // Create a journal with currency
        $journal = Journal::create([
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 1,
        ]);
        
        // The created event should have called resetCurrentBalances
        // Since there are no transactions, balance should be 0
        $this->assertEquals(0, $journal->balance->getAmount());
        $this->assertEquals('USD', $journal->balance->getCurrency()->getCode());
    }

    public function test_boot_created_event_resets_balances_with_transactions(): void
    {
        // Create a journal with currency
        $journal = Journal::create([
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 1,
        ]);
        
        // Add a transaction before the created event would be triggered again
        $journal->transactions()->create([
            'debit' => 1500,
            'credit' => 0,
            'currency' => 'USD',
            'memo' => 'Test transaction',
            'post_date' => now(),
        ]);
        
        // Manually call resetCurrentBalances to test the method fully
        $result = $journal->resetCurrentBalances();
        
        // Should return the calculated balance
        $this->assertEquals(1500, $result->getAmount());
    }

    public function test_balance_attribute_accessor_edge_cases(): void
    {
        // Test balance accessor with different scenarios
        $journal = new Journal([
            'currency' => 'GBP',
            'morphed_type' => 'test',
            'morphed_id' => 1,
        ]);
        
        // Set a balance value directly
        $journal->setRawAttributes(['balance' => 2500, 'currency' => 'GBP']);
        
        // The accessor should convert it to a Money object
        $balance = $journal->balance;
        $this->assertEquals(2500, $balance->getAmount());
        $this->assertEquals('GBP', $balance->getCurrency()->getCode());
    }

    public function test_balance_attribute_mutator_edge_cases(): void
    {
        $journal = new Journal([
            'morphed_type' => 'test',
            'morphed_id' => 1,
        ]);
        
        // Test with non-numeric string
        $journal->balance = 'invalid';
        $this->assertEquals(0, $journal->getAttributes()['balance']);
        $this->assertEquals('USD', $journal->currency); // Should default to USD
        
        // Test with null
        $journal->currency = 'EUR';
        $journal->balance = null;
        $this->assertEquals(0, $journal->getAttributes()['balance']);
    }
}