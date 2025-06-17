<?php

declare(strict_types=1);

namespace Scottlaurent\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Money\Money;
use Money\Currency;

class Ledger extends Model
{
    protected string $table = 'accounting_ledgers';
    
    protected array $fillable = [
        'name',
        'type',
    ];
    
    protected array $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    public function journals(): HasMany
    {
        return $this->hasMany(Journal::class);
    }
    
    public function journalTransactions(): HasManyThrough
    {
        return $this->hasManyThrough(JournalTransaction::class, Journal::class);
    }
    
    public function getCurrentBalance(string $currency): Money
    {
        $balance = match ($this->type) {
            'asset', 'expense' => $this->journalTransactions->sum('debit') - $this->journalTransactions->sum('credit'),
            default => $this->journalTransactions->sum('credit') - $this->journalTransactions->sum('debit'),
        };
        
        return new Money($balance, new Currency($currency));
    }
    
    public function getCurrentBalanceInDollars(): float
    {
        return $this->getCurrentBalance('USD')->getAmount() / 100;
    }
    
    public function getTypeOptions(): array
    {
        return [
            'asset' => 'Asset',
            'liability' => 'Liability',
            'equity' => 'Equity',
            'income' => 'Income',
            'expense' => 'Expense',
        ];
    }
}
