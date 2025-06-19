<?php

declare(strict_types=1);

namespace Scottlaurent\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Money\Money;
use Money\Currency;
use Scottlaurent\Accounting\Enums\LedgerType;

class Ledger extends Model
{
    protected $table = 'accounting_ledgers';
    
    protected $fillable = [
        'name',
        'type',
    ];
    
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'type' => LedgerType::class,
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
            LedgerType::ASSET, 
            LedgerType::EXPENSE,
            LedgerType::LOSS => 
                $this->journalTransactions->sum('debit') - $this->journalTransactions->sum('credit'),
            default => // LIABILITY, EQUITY, REVENUE, GAIN
                $this->journalTransactions->sum('credit') - $this->journalTransactions->sum('debit'),
        };
        
        return new Money($balance, new Currency($currency));
    }
    
    public function getCurrentBalanceInDollars(): float
    {
        return $this->getCurrentBalance('USD')->getAmount() / 100;
    }
    
    public static function getTypeOptions(): array
    {
        return array_combine(
            array_column(LedgerType::cases(), 'value'),
            array_map(fn($case) => ucfirst($case->value), LedgerType::cases())
        );
    }
}
