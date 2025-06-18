<?php

declare(strict_types=1);

namespace Scottlaurent\Accounting\Enums;

/**
 * Represents different types of ledger accounts in the accounting system.
 * Each type affects the accounting equation (Assets = Liabilities + Equity) differently.
 */
enum LedgerType: string
{
    /**
     * Represents resources owned by a company that provide future economic benefit.
     * Examples: Cash, Accounts Receivable, Inventory, Property, Equipment.
     * Normal balance: Debit (increases with debits, decreases with credits).
     */
    case ASSET = 'asset';
    
    /**
     * Represents amounts owed by the company to external parties.
     * Examples: Accounts Payable, Loans Payable, Taxes Payable.
     * Normal balance: Credit (increases with credits, decreases with debits).
     */
    case LIABILITY = 'liability';
    
    /**
     * Represents the owners' claim on the company's assets.
     * Examples: Common Stock, Retained Earnings, Owner's Capital.
     * Normal balance: Credit (increases with credits, decreases with debits).
     */
    case EQUITY = 'equity';
    
    /**
     * Represents income generated from the company's primary operations.
     * Examples: Sales Revenue, Service Revenue, Consulting Fees.
     * Normal balance: Credit (increases with credits, decreases with debits).
     */
    case REVENUE = 'revenue';
    
    /**
     * Represents costs incurred in the process of generating revenue.
     * Examples: Salaries, Rent, Utilities, Cost of Goods Sold (COGS).
     * Normal balance: Debit (increases with debits, decreases with credits).
     */
    case EXPENSE = 'expense';
    
    /**
     * Represents increases in equity from peripheral or incidental transactions.
     * Examples: Gain on Sale of Assets, Lawsuit Settlements, Insurance Recoveries.
     * Normal balance: Credit (increases with credits, decreases with debits).
     */
    case GAIN = 'gain';
    
    /**
     * Represents decreases in equity from peripheral or incidental transactions.
     * Examples: Loss on Sale of Assets, Lawsuit Settlements, Asset Impairments.
     * Normal balance: Debit (increases with debits, decreases with credits).
     */
    case LOSS = 'loss';
    
    /**
     * Gets all possible values of the LedgerType enum.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
    
    /**
     * Determines if the account type has a normal debit balance.
     *
     * @return bool True if the account type normally has a debit balance, false otherwise.
     */
    public function isDebitNormal(): bool
    {
        return in_array($this, [
            self::ASSET,
            self::EXPENSE,
            self::LOSS,
        ]);
    }
    
    /**
     * Determines if the account type has a normal credit balance.
     *
     * @return bool True if the account type normally has a credit balance, false otherwise.
     */
    public function isCreditNormal(): bool
    {
        return in_array($this, [
            self::LIABILITY,
            self::EQUITY,
            self::REVENUE,
            self::GAIN,
        ]);
    }
}
