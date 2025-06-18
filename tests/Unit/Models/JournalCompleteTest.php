<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use Tests\Unit\TestCase;
use Scottlaurent\Accounting\Models\Journal;
use Scottlaurent\Accounting\Models\Ledger;
use Scottlaurent\Accounting\Enums\LedgerType;
use Money\Money;
use Money\Currency;

class JournalCompleteTest extends TestCase
{
    public function test_balance_attribute_getter_with_different_currencies(): void
    {
        $journal = Journal::create([
            'currency' => 'JPY',
            'morphed_type' => 'test',
            'morphed_id' => 1,
        ]);
        
        // Set balance directly in attributes
        $journal->setRawAttributes(array_merge($journal->getAttributes(), ['balance' => 15000]));
        
        $balance = $journal->balance;
        $this->assertEquals(15000, $balance->getAmount());
        $this->assertEquals('JPY', $balance->getCurrency()->getCode());
    }

    public function test_balance_attribute_setter_with_zero_value(): void
    {
        $journal = new Journal([
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 1,
        ]);
        
        $journal->balance = 0;
        $this->assertEquals(0, $journal->getAttributes()['balance']);
    }

    public function test_balance_attribute_setter_with_negative_string(): void
    {
        $journal = new Journal([
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 1,
        ]);
        
        $journal->balance = '-500';
        $this->assertEquals(-500, $journal->getAttributes()['balance']);
    }

    public function test_credit_and_debit_with_null_parameters(): void
    {
        $journal = Journal::create([
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 1,
        ]);
        
        // Test credit with minimal parameters (null memo, post_date, transaction_group)
        $creditTransaction = $journal->credit(1000);
        $this->assertEquals(1000, $creditTransaction->credit);
        $this->assertNull($creditTransaction->memo);
        $this->assertNull($creditTransaction->transaction_group);
        
        // Test debit with minimal parameters
        $debitTransaction = $journal->debit(1500);
        $this->assertEquals(1500, $debitTransaction->debit);
        $this->assertNull($debitTransaction->memo);
        $this->assertNull($debitTransaction->transaction_group);
    }

    public function test_dollar_methods_with_minimal_parameters(): void
    {
        $journal = Journal::create([
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 1,
        ]);
        
        // Test creditDollars with only amount (null memo, post_date)
        $creditTransaction = $journal->creditDollars(12.34);
        $this->assertEquals(1234, $creditTransaction->credit);
        $this->assertNull($creditTransaction->memo);
        
        // Test debitDollars with only amount
        $debitTransaction = $journal->debitDollars(56.78);
        $this->assertEquals(5678, $debitTransaction->debit);
        $this->assertNull($debitTransaction->memo);
    }

    public function test_post_method_indirectly_with_different_currencies(): void
    {
        $journal = Journal::create([
            'currency' => 'EUR',
            'morphed_type' => 'test',
            'morphed_id' => 1,
        ]);
        
        $money = new Money(2500, new Currency('EUR'));
        
        // This calls the private post() method
        $transaction = $journal->credit($money, 'EUR test');
        
        $this->assertEquals(2500, $transaction->credit);
        $this->assertEquals('EUR', $transaction->currency);
        $this->assertEquals('EUR test', $transaction->memo);
    }

    public function test_reset_current_balances_different_scenarios(): void
    {
        // Test with EUR currency
        $journal = Journal::create([
            'currency' => 'EUR',
            'morphed_type' => 'test',
            'morphed_id' => 1,
        ]);
        
        // Add multiple transactions
        $journal->transactions()->createMany([
            [
                'debit' => 3000,
                'credit' => 0,
                'currency' => 'EUR',
                'memo' => 'Euro debit',
                'post_date' => now(),
            ],
            [
                'debit' => 0,
                'credit' => 1200,
                'currency' => 'EUR',
                'memo' => 'Euro credit',
                'post_date' => now(),
            ],
        ]);
        
        $result = $journal->resetCurrentBalances();
        
        // Should return balance calculated from transactions
        $this->assertEquals(1800, $result->getAmount()); // 3000 - 1200
        $this->assertEquals('EUR', $result->getCurrency()->getCode());
    }

    public function test_get_balance_on_edge_cases(): void
    {
        $journal = Journal::create([
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 1,
        ]);
        
        // Test with future date (no transactions should be included)
        $futureDate = \Carbon\Carbon::now()->addDays(10);
        $balance = $journal->getBalanceOn($futureDate);
        
        $this->assertEquals(0, $balance->getAmount());
        
        // Add a transaction and test again
        $journal->transactions()->create([
            'debit' => 1000,
            'credit' => 0,
            'currency' => 'USD',
            'memo' => 'Test',
            'post_date' => now(),
        ]);
        
        $balance = $journal->getBalanceOn($futureDate);
        $this->assertEquals(-1000, $balance->getAmount()); // Should include past transaction
    }
}