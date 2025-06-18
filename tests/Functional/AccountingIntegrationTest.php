<?php

declare(strict_types=1);

namespace Tests\Functional;

use Carbon\Carbon;
use Scottlaurent\Accounting\Models\Journal;
use Scottlaurent\Accounting\Models\Ledger;
use Scottlaurent\Accounting\Transaction;
use Tests\TestCase;

class AccountingIntegrationTest extends TestCase
{
    public function testBasicJournalTransactions()
    {
        // Create ledgers
        $cashLedger = Ledger::create([
            'name' => 'Cash Account',
            'type' => 'asset',
        ]);

        $revenueLedger = Ledger::create([
            'name' => 'Service Revenue',
            'type' => 'income',
        ]);

        // Create journals for each ledger with required fields
        $cashJournal = $cashLedger->journals()->create([
            'ledger_id' => $cashLedger->id,
            'balance' => 0,
            'currency' => 'USD',
            'memo' => 'Cash Journal',
            'post_date' => now(),
            'morphed_type' => 'test',
            'morphed_id' => 1,
        ]);
        
        $revenueJournal = $revenueLedger->journals()->create([
            'ledger_id' => $revenueLedger->id,
            'balance' => 0,
            'currency' => 'USD',
            'memo' => 'Revenue Journal',
            'post_date' => now(),
            'morphed_type' => 'test',
            'morphed_id' => 2,
        ]);
        
        // Create additional revenue journal with required fields
        $revenueJournal2 = $revenueLedger->journals()->create([
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 3,
            'balance' => 0,
        ]);

        // Initial balance check
        $this->assertEquals(0, $cashJournal->getCurrentBalanceInDollars());
        $this->assertEquals(0, $revenueJournal->getCurrentBalanceInDollars());

        // Record a service revenue transaction
        $transaction = Transaction::newDoubleEntryTransactionGroup();
        
        // Debit cash (asset increases)
        $transaction->addDollarTransaction(
            $cashJournal,
            'debit',
            150.00,
            'Service revenue received',
            null,
            Carbon::now()
        );
        
        // Credit revenue (revenue increases)
        $transaction->addDollarTransaction(
            $revenueJournal,
            'credit',
            150.00,
            'Service revenue earned',
            null,
            Carbon::now()
        );
        
        // Commit the transaction group
        $transactionGroupId = $transaction->commit();
        
        // Refresh journals to get updated balances
        $cashJournal->refresh();
        $revenueJournal->refresh();
        
        // Verify balances
        // In this implementation, debits decrease the balance and credits increase it
        // This is because getBalance() calculates as sum('debit') - sum('credit')
        $this->assertEquals(-150.00, $cashJournal->getCurrentBalanceInDollars(), 'Debit should decrease balance');
        $this->assertEquals(150.00, $revenueJournal->getCurrentBalanceInDollars(), 'Credit should increase balance');
        
        // Verify transaction was recorded
        $this->assertCount(1, $cashJournal->transactions);
        $this->assertCount(1, $revenueJournal->transactions);
    }
    
    public function testExpenseTransaction()
    {
        // Create ledgers
        $cashLedger = Ledger::create(['name' => 'Cash', 'type' => 'asset']);
        $expenseLedger = Ledger::create(['name' => 'Office Supplies', 'type' => 'expense']);
        $equityLedger = Ledger::create(['name' => 'Owner\'s Equity', 'type' => 'equity']);
        
        // Initialize journals with required fields
        $cashJournal = $cashLedger->journals()->create([
            'ledger_id' => $cashLedger->id,
            'balance' => 0,
            'currency' => 'USD',
            'memo' => 'Cash Journal',
            'post_date' => now(),
            'morphed_type' => 'test',
            'morphed_id' => 1,
        ]);
        
        $equityJournal = $equityLedger->journals()->create([
            'ledger_id' => $equityLedger->id,
            'balance' => 0,
            'currency' => 'USD',
            'memo' => 'Owner\'s Equity',
            'post_date' => now(),
            'morphed_type' => 'test',
            'morphed_id' => 2,
        ]);
        
        $expenseJournal = $expenseLedger->journals()->create([
            'ledger_id' => $expenseLedger->id,
            'balance' => 0,
            'currency' => 'USD',
            'memo' => 'Office Supplies Expense',
            'post_date' => now(),
            'morphed_type' => 'test',
            'morphed_id' => 3,
        ]);
        
        // Initial investment: Debit cash, credit owner's equity
        $transaction = Transaction::newDoubleEntryTransactionGroup();
        
        // Debit cash (asset increases)
        $transaction->addDollarTransaction(
            $cashJournal,
            'debit',
            1000.00,
            'Initial investment',
            null,
            Carbon::now()
        );
        
        // Credit owner's equity (equity increases)
        $transaction->addDollarTransaction(
            $equityJournal,
            'credit',
            1000.00,
            'Owner\'s equity',
            null,
            Carbon::now()
        );
        
        $transaction->commit();
        
        // Record an expense transaction
        $transaction = Transaction::newDoubleEntryTransactionGroup();
        
        // Debit expense (expense increases)
        $transaction->addDollarTransaction(
            $expenseJournal,
            'debit',
            75.50,
            'Office supplies purchase',
            null,
            Carbon::now()
        );
        
        // Credit cash (asset decreases)
        $transaction->addDollarTransaction(
            $cashJournal,
            'credit',
            75.50,
            'Paid for office supplies',
            null,
            Carbon::now()
        );
        
        $transaction->commit();
        
        // Refresh journals
        $cashJournal->refresh();
        $expenseJournal->refresh();
        
        // Verify ledger balances (not journal balances)
        // Refresh ledgers to get updated balances
        $cashLedger->refresh();
        $expenseLedger->refresh();
        $equityLedger->refresh();
        
        // Check cash ledger balance (asset)
        // Initial: +1000.00 (debit)
        // Expense: -75.50 (credit)
        // Expected: 1000.00 - 75.50 = 924.50
        $this->assertEquals(924.50, $cashLedger->getCurrentBalanceInDollars(), 'Cash ledger balance should be reduced by expense');
        
        // Check expense ledger balance (expense)
        // Expense: +75.50 (debit)
        // Expected: 75.50
        $this->assertEquals(75.50, $expenseLedger->getCurrentBalanceInDollars(), 'Expense ledger should show the expense amount');
        
        // Check equity ledger balance (equity)
        // Initial: +1000.00 (credit)
        // No changes
        // Expected: 1000.00
        $this->assertEquals(1000.00, $equityLedger->getCurrentBalanceInDollars(), 'Equity ledger balance should remain unchanged');
    }
}
