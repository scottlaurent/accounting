<?php

declare(strict_types=1);

namespace Tests\ComplexUseCases;

use Carbon\Carbon;
use Tests\TestCase;
use Scottlaurent\Accounting\Models\Ledger;
use Scottlaurent\Accounting\Models\Journal;
use Scottlaurent\Accounting\Enums\LedgerType;
use Scottlaurent\Accounting\Transaction;
use Money\Money;
use Money\Currency;

class CompanyFinancialScenarioTest extends TestCase
{
    /**
     * This test simulates a comprehensive financial scenario for a company
     * that utilizes all types of ledger accounts over time.
     */
    public function test_company_financial_scenario(): void
    {
        // ======================
        // 1. Company Setup
        // ======================
        
        // Create all necessary ledgers
        $assetLedgers = [
            'cash' => Ledger::create(['name' => 'Cash', 'type' => LedgerType::ASSET]),
            'accounts_receivable' => Ledger::create(['name' => 'Accounts Receivable', 'type' => LedgerType::ASSET]),
            'inventory' => Ledger::create(['name' => 'Inventory', 'type' => LedgerType::ASSET]),
            'equipment' => Ledger::create(['name' => 'Equipment', 'type' => LedgerType::ASSET]),
        ];
        
        $liabilityLedgers = [
            'accounts_payable' => Ledger::create(['name' => 'Accounts Payable', 'type' => LedgerType::LIABILITY]),
            'loans_payable' => Ledger::create(['name' => 'Loans Payable', 'type' => LedgerType::LIABILITY]),
        ];
        
        $equityLedgers = [
            'common_stock' => Ledger::create(['name' => 'Common Stock', 'type' => LedgerType::EQUITY]),
            'retained_earnings' => Ledger::create(['name' => 'Retained Earnings', 'type' => LedgerType::EQUITY]),
        ];
        
        $revenueLedgers = [
            'product_sales' => Ledger::create(['name' => 'Product Sales', 'type' => LedgerType::REVENUE]),
            'service_revenue' => Ledger::create(['name' => 'Service Revenue', 'type' => LedgerType::REVENUE]),
        ];
        
        $expenseLedgers = [
            'cogs' => Ledger::create(['name' => 'Cost of Goods Sold', 'type' => LedgerType::EXPENSE]),
            'salaries' => Ledger::create(['name' => 'Salaries Expense', 'type' => LedgerType::EXPENSE]),
            'rent' => Ledger::create(['name' => 'Rent Expense', 'type' => LedgerType::EXPENSE]),
            'utilities' => Ledger::create(['name' => 'Utilities Expense', 'type' => LedgerType::EXPENSE]),
            'depreciation' => Ledger::create(['name' => 'Depreciation Expense', 'type' => LedgerType::EXPENSE]),
        ];
        
        $gainLedgers = [
            'sale_of_asset' => Ledger::create(['name' => 'Gain on Sale of Asset', 'type' => LedgerType::GAIN]),
        ];
        
        $lossLedgers = [
            'inventory_shrinkage' => Ledger::create(['name' => 'Inventory Shrinkage', 'type' => LedgerType::LOSS]),
        ];
        
        // Create journals for each ledger
        $journals = [];
        $allLedgers = array_merge(
            $assetLedgers, $liabilityLedgers, $equityLedgers, 
            $revenueLedgers, $expenseLedgers, $gainLedgers, $lossLedgers
        );
        
        foreach ($allLedgers as $key => $ledger) {
            $journals[$key] = $ledger->journals()->create([
                'currency' => 'USD',
                'morphed_type' => 'ledger',
                'morphed_id' => $ledger->id,
            ]);
        }
        
        // ======================
        // 2. Business Transactions
        // ======================
        
        // Transaction 1: Initial investment and loan
        $this->recordTransaction([
            ['journal' => $journals['common_stock'], 'method' => 'credit', 'amount' => 100000, 'memo' => 'Initial investment'],
            ['journal' => $journals['loans_payable'], 'method' => 'credit', 'amount' => 50000, 'memo' => 'Bank loan'],
            ['journal' => $journals['cash'], 'method' => 'debit', 'amount' => 150000, 'memo' => 'Initial capital'],
        ]);
        
        // Transaction 2: Purchase inventory on account
        $this->recordTransaction([
            ['journal' => $journals['inventory'], 'method' => 'debit', 'amount' => 40000, 'memo' => 'Purchase inventory'],
            ['journal' => $journals['accounts_payable'], 'method' => 'credit', 'amount' => 40000, 'memo' => 'Owe for inventory'],
        ]);
        
        // Transaction 3: Purchase equipment with cash
        $this->recordTransaction([
            ['journal' => $journals['equipment'], 'method' => 'debit', 'amount' => 60000, 'memo' => 'Purchase equipment'],
            ['journal' => $journals['cash'], 'method' => 'credit', 'amount' => 60000, 'memo' => 'Pay for equipment'],
        ]);
        
        // Transaction 4: Pay rent for the month
        $this->recordTransaction([
            ['journal' => $journals['rent'], 'method' => 'debit', 'amount' => 5000, 'memo' => 'Monthly rent'],
            ['journal' => $journals['cash'], 'method' => 'credit', 'amount' => 5000, 'memo' => 'Pay rent'],
        ]);
        
        // Transaction 5: Sell inventory for cash and on account
        $this->recordTransaction([
            ['journal' => $journals['cash'], 'method' => 'debit', 'amount' => 35000, 'memo' => 'Cash sales'],
            ['journal' => $journals['accounts_receivable'], 'method' => 'debit', 'amount' => 25000, 'memo' => 'Credit sales'],
            ['journal' => $journals['product_sales'], 'method' => 'credit', 'amount' => 60000, 'memo' => 'Revenue from sales'],
        ]);
        
        // Record COGS
        $this->recordTransaction([
            ['journal' => $journals['cogs'], 'method' => 'debit', 'amount' => 30000, 'memo' => 'COGS for sales'],
            ['journal' => $journals['inventory'], 'method' => 'credit', 'amount' => 30000, 'memo' => 'Reduce inventory for sales'],
        ]);
        
        // Transaction 6: Pay salaries
        $this->recordTransaction([
            ['journal' => $journals['salaries'], 'method' => 'debit', 'amount' => 15000, 'memo' => 'Monthly salaries'],
            ['journal' => $journals['cash'], 'method' => 'credit', 'amount' => 15000, 'memo' => 'Pay salaries'],
        ]);
        
        // Transaction 7: Pay utilities
        $this->recordTransaction([
            ['journal' => $journals['utilities'], 'method' => 'debit', 'amount' => 2000, 'memo' => 'Monthly utilities'],
            ['journal' => $journals['cash'], 'method' => 'credit', 'amount' => 2000, 'memo' => 'Pay utilities'],
        ]);
        
        // Transaction 8: Record depreciation
        $this->recordTransaction([
            ['journal' => $journals['depreciation'], 'method' => 'debit', 'amount' => 1000, 'memo' => 'Monthly depreciation'],
            ['journal' => $journals['equipment'], 'method' => 'credit', 'amount' => 1000, 'memo' => 'Accumulated depreciation'],
        ]);
        
        // Transaction 9: Sell equipment at a gain
        $this->recordTransaction([
            ['journal' => $journals['cash'], 'method' => 'debit', 'amount' => 55000, 'memo' => 'Proceeds from equipment sale'],
            ['journal' => $journals['equipment'], 'method' => 'credit', 'amount' => 50000, 'memo' => 'Remove equipment at book value'],
            ['journal' => $journals['sale_of_asset'], 'method' => 'credit', 'amount' => 5000, 'memo' => 'Gain on sale of equipment'],
        ]);
        
        // Transaction 10: Record inventory shrinkage (theft/damage)
        $this->recordTransaction([
            ['journal' => $journals['inventory_shrinkage'], 'method' => 'debit', 'amount' => 1000, 'memo' => 'Inventory loss'],
            ['journal' => $journals['inventory'], 'method' => 'credit', 'amount' => 1000, 'memo' => 'Write off missing inventory'],
        ]);
        
        // Transaction 11: Provide services on account
        $this->recordTransaction([
            ['journal' => $journals['accounts_receivable'], 'method' => 'debit', 'amount' => 20000, 'memo' => 'Service revenue on account'],
            ['journal' => $journals['service_revenue'], 'method' => 'credit', 'amount' => 20000, 'memo' => 'Service revenue'],
        ]);
        
        // Transaction 12: Pay accounts payable
        $this->recordTransaction([
            ['journal' => $journals['accounts_payable'], 'method' => 'debit', 'amount' => 40000, 'memo' => 'Pay suppliers'],
            ['journal' => $journals['cash'], 'method' => 'credit', 'amount' => 40000, 'memo' => 'Payment to suppliers'],
        ]);
        
        // Transaction 13: Collect accounts receivable
        $this->recordTransaction([
            ['journal' => $journals['cash'], 'method' => 'debit', 'amount' => 20000, 'memo' => 'Collect from customers'],
            ['journal' => $journals['accounts_receivable'], 'method' => 'credit', 'amount' => 20000, 'memo' => 'Reduce accounts receivable'],
        ]);
        
        // ======================
        // 3. Financial Statements
        // ======================
        
        // Refresh all journals to get updated balances
        foreach ($journals as $journal) {
            $journal->refresh();
        }
        
        // Assert key account balances using ledger balances (in cents)
        $this->assertEquals(13800000, $assetLedgers['cash']->getCurrentBalance('USD')->getAmount(), 'Cash balance incorrect');
        $this->assertEquals(2500000, $assetLedgers['accounts_receivable']->getCurrentBalance('USD')->getAmount(), 'AR balance incorrect');
        $this->assertEquals(900000, $assetLedgers['inventory']->getCurrentBalance('USD')->getAmount(), 'Inventory balance incorrect');
        $this->assertEquals(900000, $assetLedgers['equipment']->getCurrentBalance('USD')->getAmount(), 'Equipment balance should be 900000 after accounting for purchase, depreciation, and sale');
        $this->assertEquals(0, $liabilityLedgers['accounts_payable']->getCurrentBalance('USD')->getAmount(), 'AP should be fully paid');
        $this->assertEquals(5000000, $liabilityLedgers['loans_payable']->getCurrentBalance('USD')->getAmount(), 'Loan balance incorrect');
        $this->assertEquals(10000000, $equityLedgers['common_stock']->getCurrentBalance('USD')->getAmount(), 'Common stock balance incorrect');
        $this->assertEquals(6000000, $revenueLedgers['product_sales']->getCurrentBalance('USD')->getAmount(), 'Product sales revenue incorrect');
        $this->assertEquals(2000000, $revenueLedgers['service_revenue']->getCurrentBalance('USD')->getAmount(), 'Service revenue incorrect');
        $this->assertEquals(3000000, $expenseLedgers['cogs']->getCurrentBalance('USD')->getAmount(), 'COGS incorrect');
        $this->assertEquals(1500000, $expenseLedgers['salaries']->getCurrentBalance('USD')->getAmount(), 'Salaries expense incorrect');
        $this->assertEquals(500000, $expenseLedgers['rent']->getCurrentBalance('USD')->getAmount(), 'Rent expense incorrect');
        $this->assertEquals(200000, $expenseLedgers['utilities']->getCurrentBalance('USD')->getAmount(), 'Utilities expense incorrect');
        $this->assertEquals(100000, $expenseLedgers['depreciation']->getCurrentBalance('USD')->getAmount(), 'Depreciation expense incorrect');
        $this->assertEquals(500000, $gainLedgers['sale_of_asset']->getCurrentBalance('USD')->getAmount(), 'Gain on sale incorrect');
        $this->assertEquals(100000, $lossLedgers['inventory_shrinkage']->getCurrentBalance('USD')->getAmount(), 'Inventory loss incorrect');
        
        // Verify accounting equation: Assets = Liabilities + Equity + Revenue - Expenses + Gains - Losses
        $totalAssets = 
            $assetLedgers['cash']->getCurrentBalance('USD')->getAmount() +
            $assetLedgers['accounts_receivable']->getCurrentBalance('USD')->getAmount() +
            $assetLedgers['inventory']->getCurrentBalance('USD')->getAmount() +
            $assetLedgers['equipment']->getCurrentBalance('USD')->getAmount();
            
        $totalLiabilities = 
            $liabilityLedgers['accounts_payable']->getCurrentBalance('USD')->getAmount() +
            $liabilityLedgers['loans_payable']->getCurrentBalance('USD')->getAmount();
            
        $totalEquity = 
            $equityLedgers['common_stock']->getCurrentBalance('USD')->getAmount() +
            $equityLedgers['retained_earnings']->getCurrentBalance('USD')->getAmount();
            
        $totalRevenue = 
            $revenueLedgers['product_sales']->getCurrentBalance('USD')->getAmount() +
            $revenueLedgers['service_revenue']->getCurrentBalance('USD')->getAmount();
            
        $totalExpenses = 
            $expenseLedgers['cogs']->getCurrentBalance('USD')->getAmount() +
            $expenseLedgers['salaries']->getCurrentBalance('USD')->getAmount() +
            $expenseLedgers['rent']->getCurrentBalance('USD')->getAmount() +
            $expenseLedgers['utilities']->getCurrentBalance('USD')->getAmount() +
            $expenseLedgers['depreciation']->getCurrentBalance('USD')->getAmount();
            
        $totalGains = $gainLedgers['sale_of_asset']->getCurrentBalance('USD')->getAmount();
        $totalLosses = $lossLedgers['inventory_shrinkage']->getCurrentBalance('USD')->getAmount();
        
        $netIncome = $totalRevenue - $totalExpenses + $totalGains - $totalLosses;
        
        // Close all temporary accounts to retained earnings in one transaction (all amounts in cents)
        $this->recordTransaction([
            // Close revenues (debit revenue accounts, credit retained earnings)
            ['journal' => $journals['product_sales'], 'method' => 'debit', 'amount' => 6000000, 'memo' => 'Close product sales revenue'],
            ['journal' => $journals['service_revenue'], 'method' => 'debit', 'amount' => 2000000, 'memo' => 'Close service revenue'],
            
            // Close gains (debit gain accounts, credit retained earnings)
            ['journal' => $journals['sale_of_asset'], 'method' => 'debit', 'amount' => 500000, 'memo' => 'Close gain on sale'],
            
            // Close expenses (credit expense accounts, debit retained earnings)
            ['journal' => $journals['cogs'], 'method' => 'credit', 'amount' => 3000000, 'memo' => 'Close COGS'],
            ['journal' => $journals['salaries'], 'method' => 'credit', 'amount' => 1500000, 'memo' => 'Close salaries'],
            ['journal' => $journals['rent'], 'method' => 'credit', 'amount' => 500000, 'memo' => 'Close rent'],
            ['journal' => $journals['utilities'], 'method' => 'credit', 'amount' => 200000, 'memo' => 'Close utilities'],
            ['journal' => $journals['depreciation'], 'method' => 'credit', 'amount' => 100000, 'memo' => 'Close depreciation'],
            
            // Close losses (credit loss accounts, debit retained earnings)
            ['journal' => $journals['inventory_shrinkage'], 'method' => 'credit', 'amount' => 100000, 'memo' => 'Close inventory loss'],
            
            // Net effect to retained earnings (revenues + gains - expenses - losses)
            // (6,000,000 + 2,000,000 + 500,000) - (3,000,000 + 1,500,000 + 500,000 + 200,000 + 100,000 + 100,000) = 3,200,000
            // This is already correctly calculated as $netIncome
            ['journal' => $journals['retained_earnings'], 'method' => 'credit', 'amount' => $netIncome, 'memo' => 'Net income for period'],
        ]);
        
        // Verify net income calculation (in cents)
        // Expected: (6,000,000 + 2,000,000) - (3,000,000 + 1,500,000 + 500,000 + 200,000 + 100,000) + 500,000 - 100,000 = 3,100,000
        $this->assertEquals(3100000, $netIncome, 'Net income calculation is incorrect');
        
        // Verify accounting equation after closing entries (all in cents)
        $totalEquityAfterClose = 
            $equityLedgers['common_stock']->getCurrentBalance('USD')->getAmount() +
            $equityLedgers['retained_earnings']->getCurrentBalance('USD')->getAmount();
        
        // Include net income in the equity calculation for the accounting equation
        $this->assertEquals(
            $totalAssets,
            $totalLiabilities + $totalEquityAfterClose + $netIncome,
            sprintf('Accounting equation does not balance: Assets (%s) != Liabilities (%s) + Equity (%s) + Net Income (%s)', 
                $totalAssets, 
                $totalLiabilities, 
                $totalEquityAfterClose,
                $netIncome)
        );
        
        // Also verify that the accounting equation balances after closing entries
        // when we include the net income in retained earnings
        $this->assertEquals(
            $totalAssets,
            $totalLiabilities + $equityLedgers['common_stock']->getCurrentBalance('USD')->getAmount() + 
            ($equityLedgers['retained_earnings']->getCurrentBalance('USD')->getAmount() + $netIncome),
            'Accounting equation does not balance after including net income in retained earnings'
        );
        
        // Verify net income calculation (in cents)
        $expectedNetIncome = 3100000; // (6,000,000 + 2,000,000) - (3,000,000 + 1,500,000 + 500,000 + 200,000 + 100,000) + 500,000 - 100,000
        $this->assertEquals(
            $expectedNetIncome,
            $netIncome,
            'Net income calculation is incorrect'
        );
    }
    
    /**
     * Helper method to record a transaction with multiple entries
     */
    private function recordTransaction(array $entries): void
    {
        $transaction = Transaction::newDoubleEntryTransactionGroup();
        
        foreach ($entries as $entry) {
            $transaction->addTransaction(
                $entry['journal'],
                $entry['method'],
                new Money($entry['amount'] * 100, new Currency('USD')),
                $entry['memo'] ?? null,
                null,
                Carbon::now()
            );
        }
        
        $transaction->commit();
    }
}
