<?php

namespace Tests\Functional;

use Tests\TestCase;
use Scottlaurent\Accounting\Models\Ledger;
use Scottlaurent\Accounting\Models\Journal;
use Scottlaurent\Accounting\Enums\LedgerType;
use Scottlaurent\Accounting\Transaction;
use Money\Money;
use Money\Currency;
use Carbon\Carbon;

class DateTimeEdgeCaseTest extends TestCase
{
    public function test_date_time_edge_cases()
    {
        // Create a test ledger
        $ledger = Ledger::create([
            'name' => 'Test Ledger',
            'type' => LedgerType::ASSET,
        ]);

        // Create a journal for the ledger
        $journal = $ledger->journals()->create([
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 1,
        ]);

        // Define test dates and times
        $testDate = Carbon::create(2025, 6, 15, 0, 0, 0, 'UTC');
        
        // Test cases with different date/time combinations
        $testCases = [
            // Same day, different times
            ['date' => (clone $testDate), 'amount' => 10000, 'desc' => 'Midnight'],
            ['date' => (clone $testDate)->addSecond(), 'amount' => 20000, 'desc' => '1 second after midnight'],
            
            // Around month boundaries
            ['date' => (clone $testDate)->endOfMonth(), 'amount' => 30000, 'desc' => 'End of month'],
            ['date' => (clone $testDate)->endOfMonth()->addSecond(), 'amount' => 40000, 'desc' => '1 second after end of month'],
            ['date' => (clone $testDate)->endOfMonth()->subSecond(), 'amount' => 50000, 'desc' => '1 second before end of month'],
            
            // Around year boundaries
            ['date' => Carbon::create(2024, 12, 31, 23, 59, 59, 'UTC'), 'amount' => 60000, 'desc' => '1 second before new year'],
            ['date' => Carbon::create(2025, 1, 1, 0, 0, 0, 'UTC'), 'amount' => 70000, 'desc' => 'New year'],
            
            // Leap seconds (simulated)
            ['date' => Carbon::create(2025, 6, 15, 23, 59, 59, 'UTC'), 'amount' => 80000, 'desc' => '1 second before next day'],
            ['date' => Carbon::create(2025, 6, 16, 0, 0, 0, 'UTC'), 'amount' => 90000, 'desc' => 'Next day'],
        ];

        // Create a second ledger and journal for the offsetting entries (e.g., a liability account)
        $offsetLedger = Ledger::create([
            'name' => 'Offset Liability Ledger',
            'type' => LedgerType::LIABILITY,
        ]);

        $offsetJournal = $offsetLedger->journals()->create([
            'currency' => 'USD',
            'morphed_type' => 'test',
            'morphed_id' => 2,
        ]);

        // Record test transactions with proper double-entry accounting
        foreach ($testCases as $case) {
            $transaction = new Transaction();
            // Debit the test journal (increases asset balance) - pass post_date as 6th parameter
            $transaction->addDollarTransaction(
                journal: $journal, 
                method: 'debit', 
                value: $case['amount'] / 100, 
                memo: $case['desc'],
                referenced_object: null, // No reference object
                postdate: $case['date']
            );
            // Credit the offset journal (e.g., a liability)
            $transaction->addDollarTransaction(
                journal: $offsetJournal, 
                method: 'credit', 
                value: $case['amount'] / 100, 
                memo: 'Offset for: ' . $case['desc'],
                referenced_object: null, // No reference object
                postdate: $case['date']
            );
            $transaction->commit();
        }

        // Use a future date to include all transactions in balance calculations
        $futureDate = Carbon::now()->addYear();
        
        // Test 1: Verify total balance (should be +4500.00 for asset account with debits)
        $this->assertEquals(450000, $journal->getBalanceOn($futureDate)->getAmount(), 'Total balance should be +4500.00');

        // Debug: Output all transactions with their dates and amounts
        $transactions = $journal->transactions()->orderBy('post_date')->get();
        echo "\n=== Transactions ===\n";
        foreach ($transactions as $tx) {
            $debit = $tx->debit;
            $credit = $tx->credit;
            $amount = $debit > 0 ? $debit : -$credit;
            echo sprintf(
                "%s - %s: %s (%s)\n",
                $tx->post_date,
                $tx->memo,
                number_format($amount / 100, 2),
                $debit > 0 ? 'debit' : 'credit'
            );
        }
        echo "\n";

        // Test 2: Verify balance at test date + 1 hour (should only include transactions up to that point)
        $balanceDate1 = $testDate->copy()->addHour();
        $balanceAtHour1 = $journal->getBalanceOn($balanceDate1)->getAmount();
        echo "Balance at {$balanceDate1}: {$balanceAtHour1}\n";

        $this->assertEquals(
            160000, // (60000 + 70000 + 10000 + 20000) - all transactions up to this point
            $balanceAtHour1,
            'Balance at test date + 1 hour should be +1600.00'
        );

        // Test 3: Verify end of month balance (should include all transactions up to July 1st)
        $endOfMonth = $testDate->copy()->endOfMonth();
        $balanceDate2 = $endOfMonth->copy()->addDay();
        $this->assertEquals(
            450000, // All transactions up to 2025-07-01
            $journal->getBalanceOn($balanceDate2)->getAmount(),
            'Balance after end of month should be +4500.00'
        );

        // Test 4: Verify end of year balance (should include all transactions)
        $balanceDate3 = $testDate->copy()->addYear();
        $this->assertEquals(
            450000, // All transactions
            $journal->getBalanceOn($balanceDate3)->getAmount(),
            'Balance after 1 year should be +4500.00'
        );

        // Test 5: Verify final balance
        $this->assertEquals(
            450000,
            $journal->getBalanceOn($futureDate)->getAmount(),
            'Final balance should be +4500.00'
        );

        // Test 6: Verify daily totals (checking debits since all transactions are debits)
        $this->assertEquals(
            (10000 + 20000 + 80000) / 100, // All transactions on the test date (converted to dollars)
            $journal->getDollarsDebitedOn($testDate),
            'Total debited on test date should be 1100.00'
        );

        // Test 7: Verify ledger balance matches journal balance
        $this->assertEquals(
            $journal->getBalance()->getAmount(),
            $ledger->getCurrentBalance('USD')->getAmount(),
            'Ledger balance should match journal balance'
        );
    }
}
