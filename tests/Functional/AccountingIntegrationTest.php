<?php

declare(strict_types=1);

namespace Tests\Functional;

use Carbon\Carbon;
use Money\Currency;
use Money\Money;
use Scottlaurent\Accounting\Models\Journal;
use Scottlaurent\Accounting\Models\JournalTransaction;
use Scottlaurent\Accounting\Models\Ledger;
use Scottlaurent\Accounting\Transaction;
use Tests\TestCase;

class AccountingIntegrationTest extends TestCase
{
    public function test_complete_transaction_flow_coverage(): void
    {
        // Test a complete transaction flow to ensure all code paths are hit
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

        // Create a complex transaction with all features
        $money1 = new Money(1500, new Currency('USD'));
        $money2 = new Money(1500, new Currency('USD'));

        // Add transactions with all possible parameters
        $transaction->addTransaction(
            $journal1,
            'debit',
            $money1,
            'Complete test debit',
            $journal2, // reference object
            \Carbon\Carbon::now()->subHours(2)
        );

        $transaction->addTransaction(
            $journal2,
            'credit',
            $money2,
            'Complete test credit',
            $journal1, // reference object
            \Carbon\Carbon::now()->subHours(1)
        );

        // This should exercise all code paths in commit()
        $transactionId = $transaction->commit();

        $this->assertIsString($transactionId);
        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $transactionId);

        // Verify the transactions were created with references
        $createdTransactions = JournalTransaction::where('transaction_group', $transactionId)->get();
        $this->assertCount(2, $createdTransactions);

        // Check that references were set
        $debitTransaction = $createdTransactions->where('journal_id', $journal1->id)->first();
        $this->assertEquals($journal2::class, $debitTransaction->ref_class);
        $this->assertEquals($journal2->id, $debitTransaction->ref_class_id);
    }

    public function testBasicJournalTransactions()
    {
        // Create ledgers
        $cashLedger = Ledger::create([
            'name' => 'Cash Account',
            'type' => 'asset',
        ]);

        $revenueLedger = Ledger::create([
            'name' => 'Service Revenue',
            'type' => 'revenue',
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
        // The system calculates balance as debit - credit
        // For asset accounts (like cash), debits should increase the balance (positive)
        // For revenue accounts, credits should increase the balance (positive)
        $this->assertEquals(150.00, $cashJournal->getCurrentBalanceInDollars(), 'Debit should increase asset balance (positive balance)');
        $this->assertEquals(-150.00, $revenueJournal->getCurrentBalanceInDollars(), 'Credit should increase revenue balance (negative in debit-credit system)');
        
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
