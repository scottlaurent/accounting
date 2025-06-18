<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use Tests\Unit\TestCase;
use Scottlaurent\Accounting\Models\Journal;
use Scottlaurent\Accounting\Models\Ledger;
use Scottlaurent\Accounting\Enums\LedgerType;
use Scottlaurent\Accounting\Models\JournalTransaction;
use Carbon\Carbon;
use Money\Money;
use Money\Currency;
use Illuminate\Database\Eloquent\Model;

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

    public function test_morphed_relationship_setup(): void
    {
        // Create a journal for testing
        $journal = Journal::create([
            'currency' => 'USD',
            'morphed_type' => 'Scottlaurent\\Accounting\\Models\\Ledger',
            'morphed_id' => 123,
        ]);

        // The morphed relationship should return a MorphTo instance
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphTo::class, $journal->morphed());
        $this->assertEquals('Scottlaurent\\Accounting\\Models\\Ledger', $journal->morphed_type);
        $this->assertEquals(123, $journal->morphed_id);
    }

    public function test_set_currency_method(): void
    {
        $journal = Journal::create([
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 1,
        ]);

        $journal->setCurrency('EUR');
        $this->assertEquals('EUR', $journal->currency);
    }

    public function test_assign_to_ledger_method(): void
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

        $journal->assignToLedger($ledger);
        $journal->refresh();
        
        $this->assertTrue($journal->ledger->is($ledger));
    }


    public function test_reset_current_balances_with_currency_and_no_transactions(): void
    {
        $journal = Journal::create([
            'currency' => 'EUR',
            'morphed_type' => 'test',
            'morphed_id' => 1,
        ]);
        
        $result = $journal->resetCurrentBalances();
        
        $this->assertEquals(0, $journal->balance->getAmount());
        $this->assertEquals('EUR', $result->getCurrency()->getCode());
    }

    public function test_reset_current_balances_with_transactions(): void
    {
        $journal = Journal::create([
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 1,
        ]);

        // Add a transaction
        $journal->transactions()->create([
            'debit' => 1000,
            'credit' => 0,
            'currency' => 'USD',
            'memo' => 'Test transaction',
            'post_date' => now(),
        ]);
        
        $result = $journal->resetCurrentBalances();
        
        $this->assertEquals(1000, $result->getAmount());
    }

    public function test_balance_attribute_with_money_object(): void
    {
        $journal = Journal::create([
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 1,
        ]);

        $money = new Money(2500, new Currency('EUR'));
        $journal->balance = $money;
        
        $this->assertEquals(2500, $journal->balance->getAmount());
        $this->assertEquals('EUR', $journal->currency);
    }

    public function test_balance_attribute_with_numeric_value_no_currency(): void
    {
        $journal = new Journal();
        $journal->morphed_type = 'test';
        $journal->morphed_id = 1;
        $journal->currency = null; // Explicitly set to null first
        
        $journal->balance = 1500;
        
        $this->assertEquals(1500, $journal->getAttributes()['balance']);
        $this->assertEquals('USD', $journal->currency); // Should default to USD
    }

    public function test_balance_attribute_with_string_value(): void
    {
        $journal = Journal::create([
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 1,
        ]);
        
        $journal->balance = '2000';
        
        $this->assertEquals(2000, $journal->balance->getAmount());
    }

    public function test_get_debit_balance_on_date(): void
    {
        $journal = Journal::create([
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 1,
        ]);

        $date = Carbon::now()->subDays(2);
        
        // Add transactions on different dates
        $journal->transactions()->create([
            'debit' => 1000,
            'credit' => 0,
            'currency' => 'USD',
            'memo' => 'Past transaction',
            'post_date' => $date,
        ]);
        
        $journal->transactions()->create([
            'debit' => 500,
            'credit' => 0,
            'currency' => 'USD',
            'memo' => 'Future transaction',
            'post_date' => Carbon::now()->addDays(1),
        ]);
        
        $balance = $journal->getDebitBalanceOn(Carbon::now());
        
        $this->assertEquals(1000, $balance->getAmount()); // Only past transaction
    }

    public function test_get_credit_balance_on_date(): void
    {
        $journal = Journal::create([
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 1,
        ]);

        $date = Carbon::now()->subDays(1);
        
        $journal->transactions()->create([
            'debit' => 0,
            'credit' => 800,
            'currency' => 'USD',
            'memo' => 'Credit transaction',
            'post_date' => $date,
        ]);
        
        $balance = $journal->getCreditBalanceOn(Carbon::now());
        
        $this->assertEquals(800, $balance->getAmount());
    }

    public function test_get_balance_on_date(): void
    {
        $journal = Journal::create([
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 1,
        ]);

        $date = Carbon::now()->subDays(1);
        
        $journal->transactions()->createMany([
            [
                'debit' => 1000,
                'credit' => 0,
                'currency' => 'USD',
                'memo' => 'Debit',
                'post_date' => $date,
            ],
            [
                'debit' => 0,
                'credit' => 300,
                'currency' => 'USD',
                'memo' => 'Credit',
                'post_date' => $date,
            ],
        ]);
        
        $balance = $journal->getBalanceOn(Carbon::now());
        
        // Credit - Debit = 300 - 1000 = -700
        $this->assertEquals(-700, $balance->getAmount());
    }

    public function test_get_current_balance(): void
    {
        $journal = Journal::create([
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 1,
        ]);
        
        $journal->transactions()->create([
            'debit' => 1200,
            'credit' => 0,
            'currency' => 'USD',
            'memo' => 'Current transaction',
            'post_date' => Carbon::now(),
        ]);
        
        $balance = $journal->getCurrentBalance();
        
        $this->assertEquals(-1200, $balance->getAmount()); // Should be negative for credit - debit
    }

    public function test_get_current_balance_in_dollars(): void
    {
        $journal = Journal::create([
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 1,
        ]);
        
        $journal->transactions()->create([
            'debit' => 1250, // $12.50
            'credit' => 0,
            'currency' => 'USD',
            'memo' => 'Dollar test',
            'post_date' => Carbon::now(),
        ]);
        
        $balance = $journal->getCurrentBalanceInDollars();
        
        $this->assertEquals(-12.50, $balance); // Should be negative for credit - debit
    }

    public function test_credit_dollars_method(): void
    {
        $journal = Journal::create([
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 1,
        ]);
        
        $transaction = $journal->creditDollars(15.75, 'Dollar credit test');
        
        $this->assertInstanceOf(JournalTransaction::class, $transaction);
        $this->assertEquals(1575, $transaction->credit); // $15.75 = 1575 cents
        $this->assertEquals('Dollar credit test', $transaction->memo);
    }

    public function test_debit_dollars_method(): void
    {
        $journal = Journal::create([
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 1,
        ]);
        
        $transaction = $journal->debitDollars(20.99, 'Dollar debit test');
        
        $this->assertInstanceOf(JournalTransaction::class, $transaction);
        $this->assertEquals(2099, $transaction->debit); // $20.99 = 2099 cents
        $this->assertEquals('Dollar debit test', $transaction->memo);
    }

    public function test_get_dollars_debited_today(): void
    {
        $journal = Journal::create([
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 1,
        ]);
        
        // Add transaction today
        $journal->transactions()->create([
            'debit' => 2500, // $25.00
            'credit' => 0,
            'currency' => 'USD',
            'memo' => 'Today debit',
            'post_date' => Carbon::now(),
        ]);
        
        // Add transaction yesterday (should not be included)
        $journal->transactions()->create([
            'debit' => 1000,
            'credit' => 0,
            'currency' => 'USD',
            'memo' => 'Yesterday debit',
            'post_date' => Carbon::now()->subDay(),
        ]);
        
        $amount = $journal->getDollarsDebitedToday();
        
        $this->assertEquals(25.00, $amount);
    }

    public function test_get_dollars_credited_today(): void
    {
        $journal = Journal::create([
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 1,
        ]);
        
        $journal->transactions()->create([
            'debit' => 0,
            'credit' => 1850, // $18.50
            'currency' => 'USD',
            'memo' => 'Today credit',
            'post_date' => Carbon::now(),
        ]);
        
        $amount = $journal->getDollarsCreditedToday();
        
        $this->assertEquals(18.50, $amount);
    }

    public function test_get_dollars_debited_on_specific_date(): void
    {
        $journal = Journal::create([
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 1,
        ]);
        
        $specificDate = Carbon::now()->subDays(3);
        $exactPostDate = $specificDate->copy()->setTime(10, 0, 0);
        
        $journal->transactions()->create([
            'debit' => 3200, // $32.00
            'credit' => 0,
            'currency' => 'USD',
            'memo' => 'Specific date debit',
            'post_date' => $exactPostDate,
        ]);
        
        $amount = $journal->getDollarsDebitedOn($specificDate);
        
        $this->assertEquals(32.00, $amount);
    }

    public function test_get_dollars_credited_on_specific_date(): void
    {
        $journal = Journal::create([
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 1,
        ]);
        
        $specificDate = Carbon::now()->subDays(2);
        $exactPostDate = $specificDate->copy()->setTime(14, 0, 0);
        
        $journal->transactions()->create([
            'debit' => 0,
            'credit' => 4750, // $47.50
            'currency' => 'USD',
            'memo' => 'Specific date credit',
            'post_date' => $exactPostDate,
        ]);
        
        $amount = $journal->getDollarsCreditedOn($specificDate);
        
        $this->assertEquals(47.50, $amount);
    }

    public function test_transactions_referencing_object_query(): void
    {
        $journal = Journal::create([
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 1,
        ]);
        
        // Create a mock model for reference
        $ledger = Ledger::create([
            'name' => 'Reference Ledger',
            'type' => LedgerType::ASSET->value,
        ]);
        
        // Create transaction with reference
        $journal->transactions()->create([
            'debit' => 1000,
            'credit' => 0,
            'currency' => 'USD',
            'memo' => 'Referenced transaction',
            'post_date' => now(),
            'ref_class' => $ledger::class,
            'ref_class_id' => $ledger->id,
        ]);
        
        // Create transaction without reference
        $journal->transactions()->create([
            'debit' => 500,
            'credit' => 0,
            'currency' => 'USD',
            'memo' => 'Non-referenced transaction',
            'post_date' => now(),
        ]);
        
        $query = $journal->transactionsReferencingObjectQuery($ledger);
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $query);
        $this->assertEquals(1, $query->count());
        $this->assertEquals('Referenced transaction', $query->first()->memo);
    }

    public function test_credit_with_money_object(): void
    {
        $journal = Journal::create([
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 1,
        ]);
        
        $money = new Money(1800, new Currency('USD'));
        $transaction = $journal->credit($money, 'Money object credit');
        
        $this->assertInstanceOf(JournalTransaction::class, $transaction);
        $this->assertEquals(1800, $transaction->credit);
        $this->assertEquals('Money object credit', $transaction->memo);
    }

    public function test_debit_with_money_object(): void
    {
        $journal = Journal::create([
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 1,
        ]);
        
        $money = new Money(2200, new Currency('USD'));
        $transaction = $journal->debit($money, 'Money object debit');
        
        $this->assertInstanceOf(JournalTransaction::class, $transaction);
        $this->assertEquals(2200, $transaction->debit);
        $this->assertEquals('Money object debit', $transaction->memo);
    }
}
